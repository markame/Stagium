<fieldset>
    <legend>Dados da empresa</legend>
    <div class="form-grid">
        <div class="field"><label for="cnpj">CNPJ</label><input id="cnpj" name="cnpj" value="{{ old('cnpj', $company->cnpj ?? '') }}" maxlength="18" inputmode="numeric" placeholder="00.000.000/0000-00" required>@error('cnpj')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="phone">Telefone</label><input id="phone" name="phone" type="tel" value="{{ old('phone', $company->phone ?? '') }}" maxlength="20" placeholder="(00) 00000-0000" required>@error('phone')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field field-wide"><label for="corporate_name">Nome empresarial</label><input id="corporate_name" name="corporate_name" value="{{ old('corporate_name', $company->corporate_name ?? '') }}" maxlength="255" required>@error('corporate_name')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field field-wide"><label for="trade_name">Nome fantasia</label><input id="trade_name" name="trade_name" value="{{ old('trade_name', $company->trade_name ?? '') }}" maxlength="255" required>@error('trade_name')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field field-wide"><label for="address">Endereço da empresa</label><input id="address" name="address" value="{{ old('address', $company->address ?? '') }}" maxlength="255" placeholder="Rua, número, bairro, cidade - UF" required>@error('address')<p class="error">{{ $message }}</p>@enderror</div>
    </div>
</fieldset>

<fieldset>
    <legend>Dados do responsável</legend>
    <div class="form-grid">
        <div class="field field-wide"><label for="responsible_name">Nome completo</label><input id="responsible_name" name="responsible_name" value="{{ old('responsible_name', $company->responsible_name ?? '') }}" maxlength="255" required>@error('responsible_name')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="responsible_cpf">CPF</label><input id="responsible_cpf" name="responsible_cpf" value="{{ old('responsible_cpf', $company->responsible_cpf ?? '') }}" maxlength="14" inputmode="numeric" placeholder="000.000.000-00" required>@error('responsible_cpf')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="responsible_rg">RG</label><input id="responsible_rg" name="responsible_rg" value="{{ old('responsible_rg', $company->responsible_rg ?? '') }}" maxlength="30" placeholder="Número do RG" required>@error('responsible_rg')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="responsible_phone">Telefone</label><input id="responsible_phone" name="responsible_phone" type="tel" value="{{ old('responsible_phone', $company->responsible_phone ?? '') }}" maxlength="20" placeholder="(00) 00000-0000" required>@error('responsible_phone')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field field-wide"><label for="responsible_address">Endereço do responsável</label><input id="responsible_address" name="responsible_address" value="{{ old('responsible_address', $company->responsible_address ?? '') }}" maxlength="255" placeholder="Rua, número, bairro, cidade - UF" required>@error('responsible_address')<p class="error">{{ $message }}</p>@enderror</div>
    </div>
</fieldset>
