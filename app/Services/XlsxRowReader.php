<?php

namespace App\Services;

use PhpZip\ZipFile;
use RuntimeException;

class XlsxRowReader
{
    /** @return array<int, array<int, string>> */
    public function read(string $path): array
    {
        $zip = new ZipFile();

        try {
            $zip->openFile($path);
            $sharedStrings = $this->sharedStrings($zip);
            $sheet = simplexml_load_string($zip->getEntryContents($this->firstSheetPath($zip)));
            throw_if($sheet === false, RuntimeException::class, 'A primeira planilha do arquivo XLSX é inválida.');
            $rows = [];

            foreach ($sheet->sheetData->row as $row) {
                $values = [];

                foreach ($row->c as $cell) {
                    $reference = (string) $cell['r'];
                    $column = $this->columnIndex($reference);
                    $type = (string) $cell['t'];
                    $value = (string) $cell->v;

                    if ($type === 's') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = $this->textFromNode($cell->is);
                    }

                    $values[$column] = $value;
                }

                if ($values !== []) {
                    $lastColumn = max(array_keys($values));
                    $rows[] = array_map(fn ($index) => $values[$index] ?? '', range(0, $lastColumn));
                }
            }

            return $rows;
        } catch (\Throwable $exception) {
            throw new RuntimeException('Não foi possível ler o arquivo XLSX. Verifique se ele não está corrompido.', previous: $exception);
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> */
    private function sharedStrings(ZipFile $zip): array
    {
        if (! in_array('xl/sharedStrings.xml', $zip->getListFiles(), true)) {
            return [];
        }

        $xml = simplexml_load_string($zip->getEntryContents('xl/sharedStrings.xml'));
        throw_if($xml === false, RuntimeException::class, 'A tabela de textos do XLSX é inválida.');

        return collect($xml->si)->map(fn ($item) => $this->textFromNode($item))->all();
    }

    private function firstSheetPath(ZipFile $zip): string
    {
        $files = $zip->getListFiles();

        if (! in_array('xl/workbook.xml', $files, true) || ! in_array('xl/_rels/workbook.xml.rels', $files, true)) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($zip->getEntryContents('xl/workbook.xml'));
        $relationships = simplexml_load_string($zip->getEntryContents('xl/_rels/workbook.xml.rels'));

        if ($workbook === false || $relationships === false || ! isset($workbook->sheets->sheet[0])) {
            return 'xl/worksheets/sheet1.xml';
        }

        $attributes = $workbook->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipId = (string) ($attributes['id'] ?? '');

        foreach ($relationships->Relationship as $relationship) {
            if ((string) $relationship['Id'] === $relationshipId) {
                $target = str_replace('\\', '/', (string) $relationship['Target']);

                return str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function textFromNode(\SimpleXMLElement $node): string
    {
        if (isset($node->t)) {
            return (string) $node->t;
        }

        return collect($node->r)->map(fn ($run) => (string) $run->t)->implode('');
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $index = 0;

        foreach (str_split(strtoupper($matches[0] ?? 'A')) as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }

        return $index - 1;
    }
}
