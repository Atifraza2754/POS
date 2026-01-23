<select class="form-select " id="tax_id" name="tax_id" data-placeholder="Choose one thing">
    @foreach ($taxes as $tax)
        <option value="{{ $tax['id'] }}" {{ $selected == $tax['id'] ? 'selected' : '' }}>{{ $tax['name'] }}</option>
    @endforeach
</select>
