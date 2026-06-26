<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Photography Platform</title>
    @vite(['resources/css/admin.css'])
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Platform Admin</h1>
                <p>Sign in to manage your photography projects</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ action([App\Http\Controllers\Admin\AuthController::class, 'login']) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="form-control" 
                        placeholder="admin@gallery.nagiyev.com" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-control" 
                        placeholder="••••••••" 
                        required
                    >
                </div>

                <div class="form-checkbox-group">
                    <label class="form-checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Remember me for 7 days</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
