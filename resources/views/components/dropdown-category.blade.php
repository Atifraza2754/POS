<select class="form-select" name="{{ $dropdownName }}">
    <option>A</option>
    <option>B</option>
    <option>C</option>
    <option>D</option>
    <option>E</option>
</select>

<select class="form-select" name="{{ $dropdownName }}">
    @foreach ($dropdownData as $option)
        <option value="{{ $option['status'] }}" {{ $selected == $option['status'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
    @endforeach
</select>