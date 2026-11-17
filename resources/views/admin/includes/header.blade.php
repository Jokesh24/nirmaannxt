<div class="header d-flex justify-content-between align-items-center px-4 py-2 bg-white shadow-sm border-bottom"
  style="height: 70px;">

  <div class="d-flex align-items-center gap-3">
    <div class="toggle-wrapper p-2 rounded-circle hover-bg-light" id="toggleBtn"
      style="cursor: pointer; transition: 0.3s;">
      <i class="bi bi-text-indent-left fs-4 text-dark"></i>
    </div>

    <div class="welcome-group d-flex flex-column">
      <span class="text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 1px;">Admin Portal</span>
      <span class="fw-semibold text-dark" style="font-size: 15px;">
        Welcome, <span style="color: #f97316;">{{ Auth::guard('admin')->user()->name }}</span>
      </span>
    </div>

    <div class="vr mx-3 text-secondary opacity-25 d-none d-lg-block" style="height: 30px;"></div>

    @php
      $currentAdminId = Auth::guard('admin')->id();

      // Fetching the previous session
      $lastLogin = \DB::table('login_logs')
        ->where('admin_id', $currentAdminId)
        ->orderBy('id', 'desc')
        ->offset(1)
        ->first();
    @endphp

    <div class="d-none d-xl-flex align-items-center gap-2 px-3 py-2 rounded-pill border"
      style="background: #f8fafc; border-color: #e2e8f0 !important; min-width: 240px;">

      <div class="position-relative">
        <i class="bi bi-shield-lock-fill text-success" style="font-size: 0.9rem;"></i>
      </div>

      <div class="d-flex flex-column" style="line-height: 1.3;">
        <span class="text-uppercase fw-bold" style="font-size: 9px; color: #64748b; letter-spacing: 0.5px;">
          Secure Last Access (IST)
        </span>

        <span style="font-size: 11px; color: #334155; font-weight: 500;">
          @if($lastLogin)
            {{ \Carbon\Carbon::parse($lastLogin->login_at)->setTimezone('Asia/Kolkata')->format('d M, h:i A') }}

            <span class="text-muted fw-normal" style="font-size: 10px;">
              <i class="bi bi-geo-alt-fill ms-1" style="font-size: 9px;"></i>
              {{ $lastLogin->location ?? 'Localhost' }}
              ({{ $lastLogin->ip_address }})
            </span>
          @else
            <span class="text-success" style="font-size: 10px;">Initial setup session active</span>
          @endif
        </span>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center gap-1">

    <!-- <div class="d-flex gap-1 me-2">
      <button class="btn btn-link text-secondary p-2 position-relative shadow-none">
        <i class="bi bi-bell fs-5"></i>
        <span
          class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
      </button>
      <button class="btn btn-link text-secondary p-2 shadow-none" title="Settings">
        <i class="bi bi-gear fs-5"></i>
      </button>
    </div> -->

    <div class="dropdown">
      <a href="#"
        class="d-flex align-items-center gap-2 text-decoration-none px-2 py-1 rounded-pill border hover-shadow-sm transition-all"
        id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="background: #fff;">
        <div class="user-avatar-wrapper position-relative">
          <img src="{{ asset('img/user-icon.png')}}" alt="User" class="rounded-circle border"
            style="width: 24px; height: 24px; object-fit: cover;" />
          <span
            class="position-absolute bottom-0 end-0 p-1 bg-success border border-2 border-white rounded-circle"></span>
        </div>
        <div class="d-none d-md-block me-1">
          <i class="bi bi-chevron-down text-muted" style="font-size: 12px;"></i>
        </div>
      </a>

      <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-3 p-2" aria-labelledby="profileDropdown"
        style="width: 260px; border-radius: 16px;">

        <li class="px-3 py-3 text-center bg-light rounded-4 mb-2">
          <div class="fw-bold text-dark">{{ Auth::guard('admin')->user()->name }}</div>
          <div class="small text-muted text-truncate">{{ Auth::guard('admin')->user()->email }}</div>
          <span class="badge mt-2" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">Super Admin</span>
        </li>

        <li><a class="dropdown-item rounded-3 py-2" href="#" data-bs-toggle="modal"
            data-bs-target="#updateProfileModal">
            <i class="bi bi-person-circle me-2 text-primary"></i> My Profile</a></li>

        <li><a class="dropdown-item rounded-3 py-2" href="{{ route('admin.changePassword') }}">
            <i class="bi bi-shield-lock me-2 text-success"></i> Change Password</a></li>

        <li>
          <hr class="dropdown-divider mx-2">
        </li>

        <li><a class="dropdown-item rounded-3 py-2 text-danger fw-600" href="{{ route('admin.logout') }}"
            onclick="return confirm('Exit the system safely?')">
            <i class="bi bi-power me-2"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</div>

<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
      <div class="modal-header border-0 pt-4 px-4">
        <h5 class="modal-title fw-bold text-dark">Profile Settings</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.profile.update') }}" method="POST">
        @csrf
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-bold text-secondary">FULL NAME</label>
              <input type="text" name="name" class="form-control form-control-lg border-light bg-light"
                value="{{ Auth::guard('admin')->user()->name }}" style="font-size: 15px; border-radius: 12px;" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold text-secondary">EMAIL ADDRESS</label>
              <input type="email" name="email" class="form-control form-control-lg border-light bg-light"
                value="{{ Auth::guard('admin')->user()->email }}" style="font-size: 15px; border-radius: 12px;"
                required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold text-secondary">MOBILE CONTACT</label>
              <input type="text" name="mobile" class="form-control form-control-lg border-light bg-light"
                value="{{ Auth::guard('admin')->user()->mobile }}" style="font-size: 15px; border-radius: 12px;">
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pb-4 px-4 pt-2">
          <button type="button" class="btn btn-link text-muted text-decoration-none"
            data-bs-dismiss="modal">Discard</button>
          <button type="submit" class="btn px-4 py-2 text-white fw-bold shadow"
            style="background: #f97316; border-radius: 12px;">Save Profile</button>
        </div>
      </form>
    </div>
  </div>
</div>