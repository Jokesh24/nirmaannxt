<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>

  @include('admin.includes.header_links')

  <style>
    /* CARDS - Compact, Premium, 8 Cards Grid */
    .insight-card {
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 4px;
      padding: 16px 18px 14px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
      transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
      height: 100%;
      position: relative;
      overflow: hidden;
    }

    .insight-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #f97316, #fb923c, #f97316);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .insight-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgba(249, 115, 22, 0.15);
      border-color: #fed7aa;
    }

    .insight-card:hover::before {
      opacity: 1;
    }

    .card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 8px;
    }

    .card-icon {
      width: 40px;
      height: 40px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 17px;
      background: linear-gradient(135deg, #fff7ed, #fed7aa);
      color: #ea580c;
      box-shadow: 0 2px 8px rgba(249, 115, 22, 0.2);
      flex-shrink: 0;
    }

    .card-label {
      font-size: 10.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.7px;
      color: #64748b;
      margin-bottom: 2px;
    }

    .card-value {
      font-size: 26px;
      font-weight: 800;
      line-height: 1;
      letter-spacing: -0.4px;
      color: #0f172a;
      margin-bottom: 1px;
    }

    .card-sub {
      font-size: 12px;
      color: #64748b;
      font-weight: 500;
    }

    .card-progress {
      margin-top: 10px;
    }

    .card-progress-bar {
      height: 5px;
      background: #e5e7eb;
      border-radius: 5px;
      overflow: hidden;
    }

    .card-progress-bar span {
      display: block;
      height: 100%;
      background: linear-gradient(90deg, #f97316, #ea580c);
      border-radius: 5px;
      box-shadow: 0 0 8px rgba(249, 115, 22, 0.4);
      transition: width 0.8s ease-out;
    }

    /* PANELS - Premium Glass-like */
    .panel {
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 4px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      margin-bottom: 32px;
    }

    .panel-header {
      padding: 16px 24px;
      background: #394249;
      border-bottom: 1px solid #fed7aa;
      font-size: 16px;
      font-weight: 700;
      color: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
    }


    .panel-header span {
      font-size: 13px;
      font-weight: 500;
      color: #fff;
    }

    /* TABLES - Striped, Attractive Headers, Status Colors */
    .table {
      margin-bottom: 0;
    }

    .table thead th {
      background: #fefce8;
      color: #92400e;
      font-size: 11.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      padding: 16px 20px;
      border: none;
      white-space: nowrap;
      position: relative;
    }



    .table tbody td {
      padding: 16px 20px;
      font-size: 14px;
      color: #374151;
      border-color: #f1f5f9;
      vertical-align: middle;
    }

    .table tbody tr:nth-child(even) {
      background: #fdfdfd;
    }

    .table tbody tr:hover {
      background: linear-gradient(90deg, #fff7ed 0%, #fef3c7 100%);
      transform: scale(1.01);
      box-shadow: inset 0 0 0 1px #fed7aa;
    }


    /* BADGES - Modern Pills */
    .badge-soft {
      padding: 6px 14px;
      font-size: 12.5px;
      font-weight: 600;
      border-radius: 20px;
      border: 1px solid rgba(0, 0, 0, 0.08);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
    }

    .badge-active {
      background: linear-gradient(135deg, #ecfdf5, #d1fae5);
      color: #047857;
    }

    .badge-pending {
      background: linear-gradient(135deg, #fff7ed, #fed7aa);
      color: #c2410c;
    }

    .badge-approved {
      background: linear-gradient(135deg, #eef2ff, #e0e7ff);
      color: #4338ca;
    }

    /* BUTTONS - Subtle Corporate */
    .btn-soft {
      padding: 8px 18px;
      font-size: 13px;
      font-weight: 600;
      border-radius: 4px;
      border: 1px solid #d1d5db;
      background: white;
      color: #374151;
      transition: all 0.2s ease;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .btn-soft:hover {
      background: #fff7ed;
      border-color: #f97316;
      color: #ea580c;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .content {
        padding: 16px;
      }

      .insight-card {
        padding: 14px 16px;
      }

      .card-value {
        font-size: 24px;
      }

      .col-xl-3 {
        flex: 0 0 100%;
        max-width: 100%;
      }
    }

    #action-preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      background: #ffffff;
      z-index: 9999999;
      display: none;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>

<body>

  <div id="action-preloader">
    <div class="spinner-border" style="color: #f97316; width: 4rem; height: 4rem; border-width: 0.25em;" role="status">
    </div>
    <h3 style="margin-top: 24px; font-weight: 800; color: #0f172a; font-size: 24px;" id="action-text">Processing Request
      Please wait...</h3>
  </div>

  @include('admin.includes.sidebar')

  @include('admin.includes.header')

  <div class="content">
    <div class="dashboard-wrapper">

      <!-- TOP CARDS -->
      <div class="row g-4 mb-5">

        <div class="col-xl-3 col-md-6">
          <div class="insight-card">
            <div class="card-top">
              <div>
                <div class="card-label">Total Vendors</div>
                <div class="card-value">128</div>
              </div>
              <div class="card-icon"><i class="bi bi-shop"></i></div>
            </div>
            <div class="card-sub">+10 this month</div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="insight-card">
            <div class="card-top">
              <div>
                <div class="card-label">Total Products</div>
                <div class="card-value">560</div>
              </div>
              <div class="card-icon"><i class="bi bi-box-seam"></i></div>
            </div>
            <div class="card-sub">120 active</div>
          </div>
        </div>



        <div class="col-xl-3 col-md-6">
          <div class="insight-card">
            <div class="card-top">
              <div>
                <div class="card-label">Coupons</div>
                <div class="card-value">75</div>
              </div>
              <div class="card-icon"><i class="bi bi-ticket-perforated"></i></div>
            </div>
            <div class="card-sub">30 active</div>
          </div>
        </div>





        <div class="col-xl-3 col-md-6">
          <div class="insight-card">
            <div class="card-top">
              <div>
                <div class="card-label">Revenue</div>
                <div class="card-value">₹6.8L</div>
              </div>
              <div class="card-icon"><i class="bi bi-currency-rupee"></i></div>
            </div>
            <div class="card-sub">+12% growth</div>
          </div>
        </div>

      </div>

      <!-- RECENT PRODUCTS -->
      <div class="panel">
        <div class="panel-header">
          Vendors
          <span>Recently joined</span>
        </div>

        <table class="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>City</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>Suresh Stores</strong></td>
              <td>Hyderabad</td>
              <td><span class="badge-soft badge-active">Active</span></td>
            </tr>

            <tr>
              <td><strong>Krishna Mart</strong></td>
              <td>Bangalore</td>
              <td><span class="badge-soft badge-pending">Pending</span></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 2 TABLES -->
      <div class="row g-4">

        <!-- Vendors -->
        <div class="col-xl-6">
          <div class="panel">

            <div class="panel-header">
              Recent Products
              <span>Last 10 added</span>
            </div>

            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Vendor</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Added</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><strong>Organic Rice</strong></td>
                    <td>Ravi Traders</td>
                    <td>₹120/kg</td>
                    <td><span class="badge-soft badge-active">Active</span></td>
                    <td>Today</td>
                  </tr>

                  <tr>
                    <td><strong>Fresh Milk</strong></td>
                    <td>Local Dairy</td>
                    <td>₹60/L</td>
                    <td><span class="badge-soft badge-pending">Pending</span></td>
                    <td>Yesterday</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>


      </div>



    </div>
  </div>

  @include('admin.includes.footer_links')


</body>

</html>

<script>
  function showActionLoader(message = 'Processing Request Please wait...') {
    $('#action-text').text(message);
    $('#action-preloader').css('display', 'flex').hide().fadeIn(200);
  }

  function hideActionLoader() {
    $('#action-preloader').fadeOut(200);
  }

  // Optional: Auto-trigger this for ALL form submissions in the dashboard globally
  $(document).on('submit', 'form', function () {
    if (!$(this).hasClass('no-loader')) {
      showActionLoader('Processing Request Please wait...');
    }
  });
</script>