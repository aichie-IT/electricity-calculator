<?php
// index.php - simple electricity calculator (vanilla PHP)

// float function
function float_in($key, $default = 0.0) {
    return isset($_POST[$key]) ? floatval(str_replace(',', '.', $_POST[$key])) : $default;
}

// Calculation function
function calculate_consumption($voltage, $current, $rate_sen, $hours = 24) {
    // Power in watts and kilowatts
    $power_w = $voltage * $current; // W
    $power_kw = $power_w / 1000.0;  // kW

    // convert rate from sen/kWh to RM/kWh
    $rate_rm_per_kwh = $rate_sen / 100.0;

    $rows = [];
    $daily_total = 0.0;

    for ($h = 1; $h <= $hours; $h++) {
        $energy_kwh = $power_kw * $h;
        $total_rm = $energy_kwh * $rate_rm_per_kwh;
        $rows[] = [
            'hour' => $h,
            'energy_kwh' => $energy_kwh,
            'total_rm' => $total_rm
        ];
        if ($h === $hours) {
            $daily_total = $total_rm;
        }
    }

    // Compute per-hour (1 hour) energy and per-day (hours) totals
    $energy_1hr = $power_kw * 1;
    $total_1hr = $energy_1hr * $rate_rm_per_kwh;

    // Total after $hours hours (full day)
    $energy_hours = $power_kw * $hours;
    $total_hours = $energy_hours * $rate_rm_per_kwh;

    return [
        'power_w' => $power_w,
        'power_kw' => $power_kw,
        'rate_rm_per_kwh' => $rate_rm_per_kwh,
        'rows' => $rows,
        'energy_1hr' => $energy_1hr,
        'total_1hr' => $total_1hr,
        'energy_hours' => $energy_hours,
        'total_hours' => $total_hours
    ];
}

// Process form
$voltage = float_in('voltage', 0.0);
$current = float_in('current', 0.0);
$rate_sen = float_in('rate_sen', 0.0);
$hours = 24;

$result = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($voltage <= 0 || $current <= 0 || $rate_sen <= 0) {
        $error = "Please enter positive numbers for Voltage, Current and Rate.";
    } else {
        $result = calculate_consumption($voltage, $current, $rate_sen, $hours);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Electricity Calculator (kWh & Charges)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!-- Bootstrap 4 CDN -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    body { padding-top: 30px; }
    .card { margin-bottom: 20px; }
    .monospace { font-family: monospace; }
  </style>
</head>
<body>
<div class="container">
  <h1 class="mb-3">Electricity Power & Charge Calculator</h1>

  <div class="card">
    <div class="card-body">
      <form method="post" novalidate>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label>Voltage (V)</label>
            <input name="voltage" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($voltage) ?>" required>
          </div>
          <div class="form-group col-md-4">
            <label>Current (A)</label>
            <input name="current" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($current) ?>" required>
          </div>
          <div class="form-group col-md-4">
            <label>Current Rate (sen / kWh)</label>
            <input name="rate_sen" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($rate_sen) ?>" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">Calculate</button>

        <small class="form-text text-muted mt-2">
          Enter rate in <strong>sen/kWh</strong> (e.g. 21.80). This converts to RM by dividing by 100.
        </small>
      </form>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($result): ?>
    <div class="card">
      <div class="card-body">
        <h5>Summary</h5>
        <p class="mb-1"><strong>Power:</strong> <?= number_format($result['power_w'], 5) ?> W (<?= number_format($result['power_kw'], 5) ?> kW)</p>
        <p class="mb-1"><strong>Rate:</strong> <?= number_format($rate_sen, 2) ?> sen/kWh = RM <?= number_format($result['rate_rm_per_kwh'], 4) ?>/kWh</p>
        <p class="mb-0"><strong>Energy in 1 hour:</strong> <?= number_format($result['energy_1hr'], 5) ?> kWh &nbsp; | &nbsp; <strong>Cost for 1 hour:</strong> RM <?= number_format($result['total_1hr'], 4) ?></p>
        <hr>
        <p class="mb-0"><strong>Energy in <?= $hours ?> hours:</strong> <?= number_format($result['energy_hours'], 5) ?> kWh &nbsp; | &nbsp; <strong>Total cost for <?= $hours ?> hours:</strong> RM <?= number_format($result['total_hours'], 4) ?></p>
      </div>
    </div>

    <div class="card">
      <div class="card-body table-responsive">
        <h5>Hour-by-hour table (cumulative)</h5>
        <table class="table table-sm table-striped table-bordered">
          <thead>
            <tr>
              <th>Hour</th>
              <th>Energy (kWh)</th>
              <th>Total (RM)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($result['rows'] as $r): ?>
              <tr>
                <td class="monospace"><?= $r['hour'] ?></td>
                <td class="monospace"><?= number_format($r['energy_kwh'], 5) ?></td>
                <td class="monospace"><?= number_format($r['total_rm'], 4) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <footer class="text-muted small">
    <p>Formulas used:
      Power (W) = Voltage (V) × Current (A) → convert to kW by dividing by 1000.
      Energy (kWh) = Power (kW) × Hours.
      Total (RM) = Energy (kWh) × (Rate in RM/kWh).
    </p>
    <p>Sample reference and layout based on calculator.pdf example and refer to TNB's official page for the latest residential tariff before entering the rate.</p>
  </footer>
</div>

<!-- Bootstrap 4 JS (optional) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
