<?php
require_once '../includes/init.php';
require_once '../includes/auth_check.php';
require_once '../includes/helpers.php';

$error = '';
$success = '';
$report_data = null;
$date_range = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $report_type = $_POST['report_type'];
        
        if (empty($start_date) || empty($end_date)) {
            throw new Exception(t('date_range_required'));
        }
        
        if ($start_date > $end_date) {
            throw new Exception(t('invalid_date_range'));
        }
        
        $date_range = format_date($start_date) . ' - ' . format_date($end_date);
        
        switch ($report_type) {
            case 'payment_summary':
                $sql = "SELECT 
                    COUNT(*) as total_facilities,
                    SUM(total_invoice_amount) as total_credit,
                    SUM(paid_amount) as total_paid,
                    SUM(remaining_balance) as total_remaining,
                    COUNT(CASE WHEN payment_status = 'unpaid' THEN 1 END) as unpaid_count,
                    COUNT(CASE WHEN payment_status = 'partial' THEN 1 END) as partial_count,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count
                FROM health_facility 
                WHERE invoice_date BETWEEN ? AND ?";
                break;
                
            case 'payment_trends':
                $sql = "SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_amount
                FROM payments 
                WHERE payment_date BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month";
                break;
                
            case 'facility_status':
                $sql = "SELECT 
                    payment_status,
                    COUNT(*) as count,
                    SUM(total_invoice_amount) as total_amount,
                    SUM(paid_amount) as paid_amount,
                    SUM(remaining_balance) as remaining_amount
                FROM health_facility 
                WHERE invoice_date BETWEEN ? AND ?
                GROUP BY payment_status";
                break;
                
            default:
                throw new Exception(t('invalid_report_type'));
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start_date, $end_date]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = t('facility_reports');
include '../includes/header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0"><?php echo t('facility_reports'); ?></h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="../dashboard/"><?php echo t('home'); ?></a></li>
            <li class="breadcrumb-item"><a href="list.php"><?php echo t('health_facilities'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo t('reports'); ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <?php if ($error): ?>
              <div class="alert alert-danger"><?php echo $error; ?></div>
              <?php endif; ?>

              <form action="" method="POST" class="mb-4">
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="start_date"><?php echo t('start_date'); ?></label>
                      <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="end_date"><?php echo t('end_date'); ?></label>
                      <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="report_type"><?php echo t('report_type'); ?></label>
                      <select class="form-control" id="report_type" name="report_type" required>
                        <option value="payment_summary"><?php echo t('payment_summary'); ?></option>
                        <option value="payment_trends"><?php echo t('payment_trends'); ?></option>
                        <option value="facility_status"><?php echo t('facility_status'); ?></option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>&nbsp;</label>
                      <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-chart-bar"></i> <?php echo t('generate_report'); ?>
                      </button>
                    </div>
                  </div>
                </div>
              </form>

              <?php if ($report_data): ?>
              <div class="report-section">
                <h4><?php echo t('report_for_period'); ?>: <?php echo $date_range; ?></h4>

                <?php if ($_POST['report_type'] === 'payment_summary'): ?>
                <div class="row">
                  <div class="col-md-3">
                    <div class="small-box bg-info">
                      <div class="inner">
                        <h3><?php echo number_format($report_data[0]['total_facilities']); ?></h3>
                        <p><?php echo t('total_facilities'); ?></p>
                      </div>
                      <div class="icon">
                        <i class="fas fa-hospital"></i>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="small-box bg-success">
                      <div class="inner">
                        <h3><?php echo format_currency($report_data[0]['total_credit']); ?></h3>
                        <p><?php echo t('total_credit'); ?></p>
                      </div>
                      <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="small-box bg-warning">
                      <div class="inner">
                        <h3><?php echo format_currency($report_data[0]['total_paid']); ?></h3>
                        <p><?php echo t('total_paid'); ?></p>
                      </div>
                      <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="small-box bg-danger">
                      <div class="inner">
                        <h3><?php echo format_currency($report_data[0]['total_remaining']); ?></h3>
                        <p><?php echo t('total_remaining'); ?></p>
                      </div>
                      <div class="icon">
                        <i class="fas fa-balance-scale"></i>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('count'); ?></th>
                        <th><?php echo t('percentage'); ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                                            $total = $report_data[0]['total_facilities'];
                                            $statuses = [
                                                'unpaid' => $report_data[0]['unpaid_count'],
                                                'partial' => $report_data[0]['partial_count'],
                                                'paid' => $report_data[0]['paid_count']
                                            ];
                                            foreach ($statuses as $status => $count):
                                            ?>
                      <tr>
                        <td><?php echo t($status); ?></td>
                        <td><?php echo number_format($count); ?></td>
                        <td><?php echo $total > 0 ? round(($count / $total) * 100, 1) : 0; ?>%</td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php endif; ?>

                <?php if ($_POST['report_type'] === 'payment_trends'): ?>
                <div class="chart">
                  <canvas id="paymentTrendsChart"
                    style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                  var ctx = document.getElementById('paymentTrendsChart').getContext('2d');
                  var data = <?php echo json_encode($report_data); ?>;

                  new Chart(ctx, {
                    type: 'line',
                    data: {
                      labels: data.map(item => item.month),
                      datasets: [{
                        label: '<?php echo t('payment_amount'); ?>',
                        data: data.map(item => item.total_amount),
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                      }]
                    },
                    options: {
                      responsive: true,
                      scales: {
                        y: {
                          beginAtZero: true
                        }
                      }
                    }
                  });
                });
                </script>
                <?php endif; ?>

                <?php if ($_POST['report_type'] === 'facility_status'): ?>
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('count'); ?></th>
                        <th><?php echo t('total_amount'); ?></th>
                        <th><?php echo t('paid_amount'); ?></th>
                        <th><?php echo t('remaining_amount'); ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($report_data as $row): ?>
                      <tr>
                        <td><?php echo t($row['payment_status']); ?></td>
                        <td><?php echo number_format($row['count']); ?></td>
                        <td><?php echo format_currency($row['total_amount']); ?></td>
                        <td><?php echo format_currency($row['paid_amount']); ?></td>
                        <td><?php echo format_currency($row['remaining_amount']); ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>