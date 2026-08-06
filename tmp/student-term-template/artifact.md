# Contrato do template - Termo de Encaminhamento 2026

- Referência retida: `C:\Users\Marcos\Downloads\1 Termo de Encaminhamento 2026.docx`
- Cópia de execução: `C:\laragon\www\Stagium\resources\templates\students\termo-encaminhamento-2026.docx`
- SHA-256 de ambas: `50DD45FB33ECC728951C42FB794CDC8BE82CC50C77AA85C7EA15D72976BCD065`
- Uma seção, uma página esperada, A4 retrato (8,27 x 11,69 pol.).
- Margens: esquerda 0,79 pol.; direita 0,79 pol.; superior 1,18 pol.; inferior 0,79 pol.
- Tipografia predominante: Times New Roman. Título e corpo usam formatação direta do modelo.
- Cabeçalho: duas imagens ancoradas (`word/media/image1.png` e `word/media/image2.png`); preservar partes, relações, âncoras e dimensões.
- Rodapé: elementos ancorados em `word/footer1.xml`; preservar integralmente.
- Sem campos Word ou controles de conteúdo.
- Tabela: 3 linhas x 2 colunas. Cabeçalho `ESTUDANTE` / `CURSO TÉCNICO`; primeira linha de dados recebe aluno e curso; segunda linha de dados fica vazia para um único aluno, sem remover linha ou alterar geometria.

## Slots editáveis

- `word/document.xml`, parágrafo 6: substituir apenas o conteúdo variável da frase iniciada por “Em decorrência...” com razão social, nome fantasia e data de início; preservar a redação jurídica fixa e a formatação do parágrafo.
- `word/document.xml`, tabela 0, linha 1: nome completo do aluno e nome do curso.
- `word/document.xml`, tabela 0, linha 2: limpar os marcadores de exemplo, preservando células e propriedades.
- `word/document.xml`, parágrafo 11: nome do gestor geral.
- `word/document.xml`, parágrafo 12: unidade IEMA na expressão `Gestor(a) Geral do IEMA Pleno ...`.
- `word/document.xml`, parágrafo 14: nome do responsável legal após `Sr(a).`.
- `word/document.xml`, parágrafo 15: nome do responsável legal.
- `word/document.xml`, parágrafo 16: cargo do responsável.

## Preservação obrigatória

- Não modificar tamanho de página, margens, seção, estilos, tema, imagens, cabeçalho, rodapé, relacionamentos, tabela ou texto jurídico fixo.
- A referência original deve permanecer byte a byte intacta.
- A saída final é PDF; o DOCX intermediário é temporário e não é entregue.
- Conferir que a saída possui uma página, cabeçalho e rodapé íntegros, tabela sem corte e todos os slots preenchidos sem sobreposição.
