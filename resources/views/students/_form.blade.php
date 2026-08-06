@php($editing = isset($student))

<div class="form-grid">
    <div class="field field-wide">
        <label for="name">Nome completo</label>
        <input id="name" name="name" type="text" value="{{ old('name', $student->name ?? '') }}" maxlength="255" required autocomplete="name">
        @error('name') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="cpf">CPF</label>
        <input id="cpf" name="cpf" type="text" value="{{ old('cpf', $student->cpf ?? '') }}" maxlength="14" required inputmode="numeric" placeholder="000.000.000-00">
        @error('cpf') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="rg">RG</label>
        <input id="rg" name="rg" type="text" value="{{ old('rg', $student->rg ?? '') }}" maxlength="30" placeholder="Número do RG">
        @error('rg') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="birth_date">Data de nascimento</label>
        <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', isset($student) ? $student->birth_date->format('Y-m-d') : '') }}" max="{{ now()->subDay()->format('Y-m-d') }}" required>
        @error('birth_date') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="phone">Telefone</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone', $student->phone ?? '') }}" maxlength="20" required autocomplete="tel" placeholder="(00) 00000-0000">
        @error('phone') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="sms_phone">Celular para SMS</label>
        <input id="sms_phone" name="sms_phone" type="tel" value="{{ old('sms_phone', $student->sms_phone ?? '') }}" maxlength="20" placeholder="(00) 00000-0000">
        @error('sms_phone') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="phone_2">Telefone 2</label>
        <input id="phone_2" name="phone_2" type="tel" value="{{ old('phone_2', $student->phone_2 ?? '') }}" maxlength="20">
        @error('phone_2') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="phone_3">Telefone 3</label>
        <input id="phone_3" name="phone_3" type="tel" value="{{ old('phone_3', $student->phone_3 ?? '') }}" maxlength="20">
        @error('phone_3') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field field-wide">
        <label for="other_phones">Outros telefones</label>
        <input id="other_phones" name="other_phones" type="text" value="{{ old('other_phones', $student->other_phones ?? '') }}" maxlength="255">
        @error('other_phones') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="course_id">Curso</label>
        <select id="course_id" name="course_id" required>
            <option value="">Selecione um curso</option>
            @foreach ($courses as $course)
                <option value="{{ $course->id }}" @selected((string) old('course_id', $student->course_id ?? '') === (string) $course->id)>{{ $course->name }}</option>
            @endforeach
        </select>
        @error('course_id') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field field-wide">
        <label for="address">Endereço</label>
        <input id="address" name="address" type="text" value="{{ old('address', $student->address ?? '') }}" maxlength="255" required autocomplete="street-address" placeholder="Rua, número, bairro, cidade - UF">
        @error('address') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field field-wide">
        <label for="parentage">Filiação</label>
        <input id="parentage" name="parentage" type="text" value="{{ old('parentage', $student->parentage ?? '') }}" maxlength="255" required placeholder="Nome da mãe, do pai ou responsáveis">
        @error('parentage') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>
