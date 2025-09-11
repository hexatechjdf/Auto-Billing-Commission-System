@extends('layouts.app')

@section('title', 'Dashboard - Auto-Billing Commission System')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <div class="d-flex align-items-center">
            <span class="status-badge {{ $agencyConnected ? 'status-connected' : 'status-disconnected' }}">
                <i class="fas fa-{{ $agencyConnected ? 'check-circle' : 'times-circle' }} me-1"></i>
                {{ $agencyConnected ? 'Agency Connected' : 'Agency Disconnected' }}
            </span>
        </div>
    </div>

    @if (!$agencyConnected)
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-3"></i>
            <div>
                <strong>Agency Not Connected!</strong> Please connect your GoHighLevel agency to start using the
                auto-billing system.
                <a href="#" class="alert-link ms-2">Connect Now</a>
            </div>
        </div>
    @endif

    <!-- Metrics Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="metric-card">
                <div class="metric-value">$12,450</div>
                <div class="metric-label">Total Revenue This Month</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="metric-card">
                <div class="metric-value">156</div>
                <div class="metric-label">Active Subscriptions</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="metric-card">
                <div class="metric-value">23</div>
                <div class="metric-label">Failed Payments</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="metric-card">
                <div class="metric-value">98.5%</div>
                <div class="metric-label">Success Rate</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Row -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="{{ route('admin.subaccounts') }}" class="btn btn-outline-primary">
                            <i class="fas fa-building me-2"></i>
                            Manage Subaccounts
                        </a>
                        <a href="{{ route('admin.plan-mappings.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-layer-group me-2"></i>
                            Configure Plan Mappings
                        </a>
                        <button class="btn btn-outline-success" onclick="runCommissionBatch()">
                            <i class="fas fa-play me-2"></i>
                            Run Commission Batch
                        </button>
                        <a href="{{ route('admin.transactions') }}" class="btn btn-outline-info">
                            <i class="fas fa-chart-bar me-2"></i>
                            View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Recent Alerts
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning alert-sm mb-2">
                        <small><strong>Payment Failed:</strong> Location ABC123 - $99.99</small>
                    </div>
                    <div class="alert alert-info alert-sm mb-2">
                        <small><strong>New Order:</strong> Location XYZ789 - $29.99</small>
                    </div>
                    <div class="alert alert-success alert-sm mb-0">
                        <small><strong>Commission Processed:</strong> Batch #1234 completed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Event</th>
                                    <th>Location</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2 minutes ago</td>
                                    <td>Commission Charge</td>
                                    <td>Main Location</td>
                                    <td>$5.99</td>
                                    <td><span class="badge bg-success">Success</span></td>
                                </tr>
                                <tr>
                                    <td>15 minutes ago</td>
                                    <td>New Order</td>
                                    <td>Branch Location</td>
                                    <td>$299.99</td>
                                    <td><span class="badge bg-info">Processed</span></td>
                                </tr>
                                <tr>
                                    <td>1 hour ago</td>
                                    <td>Payment Failed</td>
                                    <td>Remote Office</td>
                                    <td>$99.99</td>
                                    <td><span class="badge bg-danger">Failed</span></td>
                                </tr>
                                <tr>
                                    <td>2 hours ago</td>
                                    <td>Commission Charge</td>
                                    <td>Main Location</td>
                                    <td>$2.99</td>
                                    <td><span class="badge bg-success">Success</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function runCommissionBatch() {
            if (confirm('Are you sure you want to run a commission batch now?')) {
                const btn = event.target;
                showLoading(btn);

                // Simulate API call
                setTimeout(() => {
                    hideLoading(btn);
                    toastr.success('Commission batch started successfully!');
                }, 2000);
            }
        }
    </script>
@endpush
