<div class="row g-2 mb-2 operation-row">
    <div class="col-md-1"><input type="number" min="1" class="form-control" name="operations[{{ $index }}][sequence]" value="{{ $line['sequence'] ?? '' }}" placeholder="Seq" required></div>
    <div class="col-md-2">
        <select name="operations[{{ $index }}][manufacturing_operation_id]" class="form-select" required>
            <option value="">Operation</option>
            @foreach ($manufacturingOperations as $op)
                <option value="{{ $op->id }}" @selected((string) ($line['manufacturing_operation_id'] ?? '') === (string) $op->id)>{{ $op->code }} — {{ $op->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="operations[{{ $index }}][work_centre_id]" class="form-select work-centre">
            <option value="">Work centre</option>
            @foreach ($workCentres as $wc)
                <option value="{{ $wc->id }}" data-machine="{{ $wc->machine_rate_per_hour }}" data-labour="{{ $wc->labour_rate_per_hour }}" @selected((string) ($line['work_centre_id'] ?? '') === (string) $wc->id)>{{ $wc->code }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" class="form-control" name="operations[{{ $index }}][setup_time_minutes]" value="{{ $line['setup_time_minutes'] ?? 0 }}" placeholder="Setup"></div>
    <div class="col-md-1"><input type="number" step="0.0001" min="0.0001" class="form-control" name="operations[{{ $index }}][run_time_per_unit_minutes]" value="{{ $line['run_time_per_unit_minutes'] ?? '' }}" placeholder="Run" required></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" class="form-control machine-rate" name="operations[{{ $index }}][machine_rate_per_hour]" value="{{ $line['machine_rate_per_hour'] ?? 0 }}" placeholder="M rate"></div>
    <div class="col-md-1"><input type="number" step="0.01" min="0" class="form-control labour-rate" name="operations[{{ $index }}][labour_rate_per_hour]" value="{{ $line['labour_rate_per_hour'] ?? 0 }}" placeholder="L rate"></div>
    <div class="col-md-1"><input type="number" min="0" max="20" class="form-control" name="operations[{{ $index }}][operators_required]" value="{{ $line['operators_required'] ?? 1 }}"></div>
    <div class="col-md-1 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="operations[{{ $index }}][is_outsourced]" value="1" @checked(!empty($line['is_outsourced']))><label class="form-check-label">Out</label></div></div>
    <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-operation"><i class="ri-close-line"></i></button></div>
</div>
