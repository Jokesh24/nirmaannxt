<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | RBM Admin</title>
    @include('admin.includes.header_links')
    <style>
        :root {
            --primary-orange: #f97316;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            /* Prevents scroll on high res */
        }

        .content {
            padding: 20px 30px;
            /* Reduced vertical padding */
        }

        .security-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            width: 100%;
        }

        .input-group-custom .form-control {
            border-radius: 10px 0 0 10px !important;
            border-right: none;
            padding: 10px 15px;
            /* Tighter padding */
            font-size: 0.9rem;
            border: 1px solid var(--border-color);
        }

        .input-group-custom .form-control:focus {
            border-color: var(--primary-orange);
            box-shadow: none;
        }

        .password-toggle-btn {
            background: #fff;
            border: 1px solid var(--border-color);
            border-left: none;
            color: var(--text-muted);
            padding: 0 12px;
            cursor: pointer;
            border-radius: 0 10px 10px 0 !important;
        }

        .strength-meter {
            height: 4px;
            background-color: #e2e8f0;
            margin-top: 6px;
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }

        .strength-meter-fill {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }

        .guideline-sidebar {
            background: #ffffff;
            border-top: 3px solid var(--primary-orange);
            /* Horizontal border for compact look */
            border-radius: 12px;
        }

        .security-icon-circle {
            width: 40px;
            height: 40px;
            background: #fff8f4;
            color: var(--primary-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .btn-save-lg {
            background-color: var(--primary-orange);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 0.4rem;
        }

        .compact-alert {
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Account Security</h4>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="history.back()"
                style="border-radius: 8px;">
                <i class="bi bi-arrow-left me-1"></i> Back
            </button>
        </div>

        <div class="row g-3">
            <div class="col-xl-12">
                <div class="security-card p-4 shadow-sm">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="security-icon-circle">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Update Credentials</h5>
                            <p class="text-muted small mb-0">Modify your administrative password below.</p>
                        </div>
                    </div>

                    @if(Session::has('success'))
                        <div class="alert alert-success border-0 shadow-sm compact-alert d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill me-2"></i> {{ Session::get('success') }}
                        </div>
                    @endif

                    @if(Session::has('error'))
                        <div class="alert alert-danger border-0 shadow-sm compact-alert d-flex align-items-center mb-3">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i> {{ Session::get('error') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.updatePassword') }}" method="POST" autocomplete="off">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Current Password</label>
                                <div class="input-group-custom">
                                    <input type="password" name="old_password" id="old_password" class="form-control"
                                        placeholder="Type current password" required>
                                    <button class="password-toggle-btn" type="button"
                                        onclick="togglePass('old_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('old_password') <div class="text-danger mt-1" style="font-size: 0.7rem;">
                                {{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <div class="input-group-custom">
                                    <input type="password" name="new_password" id="new_password" class="form-control"
                                        placeholder="Enter new password" required onkeyup="checkStrength(this.value)">
                                    <button class="password-toggle-btn" type="button"
                                        onclick="togglePass('new_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="strength-meter" id="strength-meter">
                                    <div class="strength-meter-fill" id="strength-bar"></div>
                                </div>
                                <div id="strength-text" class="mt-1" style="font-size: 0.65rem; font-weight: 800;">
                                </div>
                                @error('new_password') <div class="text-danger mt-1" style="font-size: 0.7rem;">
                                {{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group-custom">
                                    <input type="password" name="confirm_password" id="confirm_password"
                                        class="form-control" placeholder="Repeat new password" required>
                                    <button class="password-toggle-btn" type="button"
                                        onclick="togglePass('confirm_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('confirm_password') <div class="text-danger mt-1" style="font-size: 0.7rem;">
                                {{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mt-4 p-3 guideline-sidebar border shadow-sm">
                            <div class="row align-items-center">
                                <div class="col-md-9 border-end">
                                    <h6 class="fw-bold small mb-2 d-flex align-items-center">
                                        <i class="bi bi-info-circle-fill text-primary me-2"></i> Password Complexity
                                        Rules
                                    </h6>
                                    <div class="d-flex gap-4">
                                        <span class="small text-muted" style="font-size: 0.75rem;"><i
                                                class="bi bi-check2 text-success"></i> 8+ Characters</span>
                                        <span class="small text-muted" style="font-size: 0.75rem;"><i
                                                class="bi bi-check2 text-success"></i> Upper/Lower case</span>
                                        <span class="small text-muted" style="font-size: 0.75rem;"><i
                                                class="bi bi-check2 text-success"></i> Symbols & Numbers</span>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <button type="submit" class="btn-save-lg shadow-sm w-100">
                                        Update Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <script>
        function togglePass(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        function checkStrength(password) {
            const meter = document.getElementById('strength-meter');
            const bar = document.getElementById('strength-bar');
            const text = document.getElementById('strength-text');
            if (password.length > 0) { meter.style.display = 'block'; } else { meter.style.display = 'none'; text.innerText = ""; return; }
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            switch (strength) {
                case 0: case 1: bar.style.width = '25%'; bar.style.backgroundColor = '#ef4444'; text.innerText = "WEAK"; text.style.color = "#ef4444"; break;
                case 2: bar.style.width = '50%'; bar.style.backgroundColor = '#f59e0b'; text.innerText = "MEDIUM"; text.style.color = "#f59e0b"; break;
                case 3: bar.style.width = '75%'; bar.style.backgroundColor = '#3b82f6'; text.innerText = "STRONG"; text.style.color = "#3b82f6"; break;
                case 4: bar.style.width = '100%'; bar.style.backgroundColor = '#22c55e'; text.innerText = "VERY SECURE"; text.style.color = "#22c55e"; break;
            }
        }
    </script>
</body>

</html>