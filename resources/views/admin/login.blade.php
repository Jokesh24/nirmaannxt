<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | A1 Build CART Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Inter", sans-serif;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* --- Page Preloader --- */
        #page-preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .preloader-content {
            background: #ffffff;
            padding: 40px 60px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.1);
            text-align: center;
        }

        #page-preloader.fade-out {
            opacity: 0;
            visibility: hidden;
        }

        /* --- Action Preloader (Solid Blank Screen for Redirects) --- */
        #action-preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: #ffffff;
            z-index: 9999999;
            display: none;
            /* Handled via JS */
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* ----------------------------------- */

        .login-wrapper {
            width: 100%;
            max-width: 1180px;
            min-height: 680px;
            background: #ffffff;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.12);
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
        }

        .login-left {
            position: relative;
            padding: 60px 56px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .login-left::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.10);
            border-radius: 50%;
            top: -70px;
            right: -70px;
        }

        .login-left::after {
            content: "";
            position: absolute;
            width: 340px;
            height: 340px;
            background: rgba(255, 255, 255, 0.07);
            border-radius: 50%;
            bottom: -120px;
            left: -100px;
        }

        .brand {
            position: relative;
            z-index: 2;
        }

        .brand-badge {
            width: 62px;
            height: 62px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .brand h1 {
            font-size: 38px;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 16px;
        }

        .brand p {
            font-size: 16px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.92);
            max-width: 520px;
            margin-bottom: 0;
        }

        .feature-boxes {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 40px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .feature-card i {
            font-size: 22px;
            margin-bottom: 14px;
            display: inline-block;
        }

        .feature-card h6 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .feature-card p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.88);
            margin: 0;
            line-height: 1.7;
        }

        .login-right {
            padding: 60px 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
        }

        .login-top {
            margin-bottom: 32px;
        }

        .login-top h2 {
            font-size: 34px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .login-top p {
            color: #64748b;
            font-size: 15px;
            margin: 0;
        }

        .form-label {
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-group.custom-group {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            transition: all 0.25s ease;
        }

        .input-group.custom-group:focus-within {
            border-color: #f97316;
            box-shadow: 0 0 0 0.18rem rgba(249, 115, 22, 0.14);
        }

        .input-group-text {
            background: #fff;
            border: none;
            color: #94a3b8;
            padding-left: 16px;
            padding-right: 12px;
        }

        .form-control {
            border: none;
            height: 56px;
            padding: 0 16px 0 0;
            font-size: 14px;
            font-weight: 500;
            color: #0f172a;
            box-shadow: none !important;
        }

        .form-control::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .toggle-password {
            border: none;
            background: transparent;
            color: #94a3b8;
            padding: 0 16px;
            font-size: 18px;
        }

        .form-extra {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            margin-bottom: 26px;
            flex-wrap: wrap;
        }

        .form-check-label {
            font-size: 14px;
            color: #475569;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 14px;
            font-weight: 600;
            color: #f97316;
            text-decoration: none;
        }

        .forgot-link:hover {
            color: #ea580c;
        }

        .login-btn {
            width: 100%;
            height: 56px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            box-shadow: 0 18px 30px rgba(249, 115, 22, 0.24);
            transition: all 0.25s ease;
        }

        .login-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 22px 36px rgba(249, 115, 22, 0.30);
        }

        .login-btn:disabled {
            opacity: 0.8;
            cursor: not-allowed;
        }

        .login-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }

        .login-footer a {
            color: #f97316;
            font-weight: 700;
            text-decoration: none;
        }

        .copyright-text {
            margin-top: 30px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }

        @media (max-width: 991px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .login-left,
            .login-right {
                padding: 42px 28px;
            }

            .brand h1 {
                font-size: 30px;
            }

            .feature-boxes {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 14px;
            }

            .login-wrapper {
                border-radius: 24px;
            }

            .login-left,
            .login-right {
                padding: 28px 20px;
            }

            .login-top h2 {
                font-size: 28px;
            }

            .brand h1 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

    <!-- Initial Page Preloader -->
    <div id="page-preloader">
        <div class="preloader-content">
            <div class="spinner-border" style="color: #f97316; width: 3.5rem; height: 3.5rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h3 style="margin-top: 24px; font-weight: 800; color: #0f172a; font-size: 24px;">Nirmaan NXT</h3>
            <p style="margin: 0; color: #64748b; font-size: 15px; font-weight: 500;">Loading Admin Panel...</p>
        </div>
    </div>

    <!-- Action Preloader (Redirecting Blank Screen Overlay) -->
    <div id="action-preloader">
        <div class="spinner-border" style="color: #f97316; width: 4rem; height: 4rem; border-width: 0.25em;"
            role="status"></div>
        <h3 style="margin-top: 24px; font-weight: 800; color: #0f172a; font-size: 24px;" id="action-text">We are
            Redirecting to Admin Dashboard Please wait...</h3>
    </div>

    <div class="login-wrapper">

        <div class="login-left">
            <div class="brand">
                <div class="brand-badge">
                    <i class="bi bi-grid-1x2-fill"></i>
                </div>
                <h1>Welcome to<br>A1 Build CART Admin Panel</h1>
                <p>Manage vendors, products and more — all in one powerful dashboard.</p>
            </div>

            <div class="feature-boxes">
                <div class="feature-card">
                    <i class="bi bi-people-fill"></i>
                    <h6>Vendor Management</h6>
                    <p>Organize and manage all vendor-related records with ease.</p>
                </div>
                <div class="feature-card">
                    <i class="bi bi-box-seam-fill"></i>
                    <h6>Warehouse Control</h6>
                    <p>Track warehouses, categories, locations, and operational status.</p>
                </div>
                <div class="feature-card">
                    <i class="bi bi-award-fill"></i>
                    <h6>Membership Plans</h6>
                    <p>Create and manage membership plans with a structured workflow.</p>
                </div>
                <div class="feature-card">
                    <i class="bi bi-bar-chart-fill"></i>
                    <h6>Admin Insights</h6>
                    <p>Keep everything organized with a clean and scalable admin system.</p>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-card">

                <div class="login-top">
                    <h2>Sign In</h2>
                    <p>Enter your credentials to access the dashboard.</p>
                </div>

                <form action="{{ route('admin.authenticate') }}" method="post" id="loginForm" autocomplete="off">
                    @csrf

                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    <input type="hidden" name="location_data" id="location_data">

                    @if(Session::has('error'))
                        <div class="alert alert-danger py-2" style="border-radius: 12px; font-size: 14px;">
                            {{ Session::get('error') }}
                        </div>
                    @endif

                    <div class="mb-4">
                        <label class="form-label">Email Address</label>
                        <div class="input-group custom-group {{ $errors->has('email') ? 'border-danger' : '' }}">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}"
                                placeholder="Enter your email" required>
                        </div>
                        @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Password</label>
                        <div class="input-group custom-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Enter your password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-extra">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="rememberMe" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                        </div>
                        <a href="javascript:void(0)" onclick="openForgotModal()" class="forgot-link">Forgot
                            Password?</a>
                    </div>

                    <button type="submit" class="login-btn" id="submitBtn">
                        <span id="btnText">Login to Dashboard</span>
                        <span class="spinner-border spinner-border-sm d-none" id="btnLoader"></span>
                    </button>

                    <div class="login-footer">
                        Need help? <a href="#">Contact Support</a>
                    </div>

                    <div class="copyright-text">
                        © {{ date('Y') }} A1 Build CART Admin. All rights reserved.
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 24px; border: none;">
                <div class="modal-body p-5">
                    <div class="text-center mb-4">
                        <div class="brand-badge mx-auto mb-3" style="background: #fff7ed; color: #f97316;">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h4 class="fw-bold">Account Recovery</h4>
                        <p class="text-muted small" id="modalInstruction">Enter your email to receive an OTP.</p>
                    </div>

                    <div id="step-email">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" id="forgot_email" class="form-control bg-light border-0"
                                style="border-radius: 12px;" placeholder="example@mail.com">
                        </div>
                        <button type="button" onclick="sendOtp(this)" class="login-btn">Send OTP</button>
                    </div>

                    <div id="step-otp" style="display: none;">
                        <div class="alert alert-warning py-2 text-center" style="border-radius: 12px; font-size: 13px;">
                            OTP sent to <span id="display_mobile"></span><br>
                            <strong>TEST OTP: <span id="test_otp" class="text-danger"></span></strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Enter 6-Digit OTP</label>
                            <input type="text" id="otp_code"
                                class="form-control bg-light border-0 text-center fw-bold fs-4" maxlength="6"
                                style="border-radius: 12px; letter-spacing: 5px;">
                        </div>
                        <button type="button" onclick="verifyOtp(this)" class="login-btn">Verify & Proceed</button>
                    </div>

                    <div id="step-password" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" id="reset_password" class="form-control bg-light border-0"
                                style="border-radius: 12px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" id="reset_confirm" class="form-control bg-light border-0"
                                style="border-radius: 12px;">
                        </div>
                        <button type="button" onclick="resetPassword(this)" class="login-btn">Reset Password</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // --- Page Preloader Script ---
        window.addEventListener('load', function () {
            const preloader = document.getElementById('page-preloader');
            if (preloader) {
                preloader.classList.add('fade-out');
                setTimeout(() => {
                    preloader.style.display = 'none';
                }, 500);
            }
        });

        // --- Action Loader Script ---
        function showActionLoader(message) {
            $('#action-text').text(message);
            $('#action-preloader').css('display', 'flex').hide().fadeIn(200);
        }

        function hideActionLoader() {
            $('#action-preloader').fadeOut(200);
        }

        // Password Toggle
        function togglePassword() {
            const password = document.getElementById("password");
            const eyeIcon = document.getElementById("eyeIcon");
            if (password.type === "password") {
                password.type = "text";
                eyeIcon.classList.remove("bi-eye");
                eyeIcon.classList.add("bi-eye-slash");
            } else {
                password.type = "password";
                eyeIcon.classList.remove("bi-eye-slash");
                eyeIcon.classList.add("bi-eye");
            }
        }

        // --- Form Interceptor with Action Loader ---
        $('#loginForm').on('submit', function (e) {
            // If location is already fetched, show loader and allow native submit
            if ($('#location_data').val() !== "") {
                showActionLoader('We are Redirecting to Admin Dashboard Please wait...');
                return true;
            }

            e.preventDefault();
            const btn = $('#submitBtn');
            const text = $('#btnText');
            const loader = $('#btnLoader');
            const form = this;

            btn.prop('disabled', true);
            loader.removeClass('d-none');
            text.text("Verifying...");

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    async function (position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        $('#latitude').val(lat);
                        $('#longitude').val(lon);

                        try {
                            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`);
                            const data = await res.json();
                            const addr = data.address;
                            const area = addr.suburb || addr.neighbourhood || addr.residential || "";
                            const city = addr.city || addr.town || "";
                            const state = addr.state || "";
                            $('#location_data').val(`${area}, ${city}, ${state}`.replace(/^, /, ''));
                        } catch (err) {
                            $('#location_data').val("GPS Active");
                        }
                        // Location fetched successfully - Trigger overlay and submit form
                        showActionLoader('We are Redirecting to Admin Dashboard Please wait...');
                        form.submit();
                    },
                    function (error) {
                        $('#location_data').val("Permission Denied");
                        // Fallback - Trigger overlay and submit form
                        showActionLoader('We are Redirecting to Admin Dashboard Please wait...');
                        form.submit();
                    },
                    { enableHighAccuracy: true, timeout: 5000 }
                );
            } else {
                showActionLoader('We are Redirecting to Admin Dashboard Please wait...');
                form.submit();
            }
        });

        // --- Secure AJAX Handlers (with loader integrated) ---
        var forgotModal = new bootstrap.Modal(document.getElementById('forgotModal'));

        function openForgotModal() {
            $('#step-email').show();
            $('#step-otp, #step-password').hide();
            forgotModal.show();
        }

        function sendOtp(btn) {
            let email = $('#forgot_email').val();
            if (!email) return alert("Please enter email");

            $(btn).prop('disabled', true).text('Sending...');
            showActionLoader('Sending OTP Please wait...');

            $.post("{{ route('admin.sendOtp') }}", {
                email: email,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function (res) {
                if (res.status == 200) {
                    $('#display_mobile').text(res.mobile);
                    $('#test_otp').text(res.otp);
                    $('#step-email').hide();
                    $('#step-otp').fadeIn();
                } else {
                    alert(res.message);
                }
            }).always(() => {
                hideActionLoader();
                $(btn).prop('disabled', false).text('Send OTP');
            });
        }

        function verifyOtp(btn) {
            let email = $('#forgot_email').val();
            let otp = $('#otp_code').val();

            $(btn).prop('disabled', true).text('Verifying...');
            showActionLoader('Verifying OTP Please wait...');

            $.post("{{ route('admin.verifyOtp') }}", {
                email: email,
                otp: otp,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function (res) {
                if (res.status == 200) {
                    $('#step-otp').hide();
                    $('#step-password').fadeIn();
                } else {
                    alert(res.message);
                }
            }).always(() => {
                hideActionLoader();
                $(btn).prop('disabled', false).text('Verify & Proceed');
            });
        }

        function resetPassword(btn) {
            let data = {
                email: $('#forgot_email').val(),
                new_password: $('#reset_password').val(),
                confirm_password: $('#reset_confirm').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            $(btn).prop('disabled', true).text('Updating...');
            showActionLoader('Resetting Password Please wait...');

            $.post("{{ route('admin.resetPassword') }}", data, function (res) {
                if (res.status == 200) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            }).always(() => {
                hideActionLoader();
                $(btn).prop('disabled', false).text('Reset Password');
            });
        }
    </script>
</body>

</html>