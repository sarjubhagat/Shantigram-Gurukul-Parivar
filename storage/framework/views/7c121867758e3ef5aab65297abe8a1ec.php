<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?php echo e(config('app.name')); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --cr:   #8B1A1A;
            --cr-d: #6D1313;
            --cr-a: #C0392B;
            --gold: #C9A227;
            --gold-l: #F0D070;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            background: #F8F3F3;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Left panel ── */
        .login-left {
            width: 42%;
            background: linear-gradient(160deg, var(--cr) 0%, var(--cr-d) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
            position: relative;
            overflow: hidden;
        }

        /* decorative circles */
        .login-left::before,
        .login-left::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: .1;
        }
        .login-left::before {
            width: 380px; height: 380px;
            background: #fff;
            top: -120px; right: -120px;
        }
        .login-left::after {
            width: 260px; height: 260px;
            background: var(--gold);
            bottom: -80px; left: -80px;
        }

        .ll-inner { position: relative; z-index: 1; text-align: center; max-width: 320px; }

        .ll-emblem {
            width: 80px; height: 80px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-l) 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; color: #5A1010;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 30px rgba(0,0,0,.25), 0 0 0 1px rgba(255,255,255,.15);
        }

        .ll-title {
            color: #fff;
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0 0 .5rem;
            letter-spacing: -.4px;
            line-height: 1.2;
        }

        .ll-tagline {
            color: rgba(255,224,224,.75);
            font-size: .88rem;
            font-weight: 400;
            margin: 0 0 2.5rem;
            line-height: 1.5;
        }

        .ll-divider {
            width: 48px; height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-l));
            border-radius: 2px;
            margin: 0 auto 2rem;
        }

        .ll-quote {
            font-size: .82rem;
            color: rgba(255,220,220,.65);
            font-style: italic;
            line-height: 1.65;
            border-left: 2px solid rgba(201,162,39,.5);
            padding-left: 1rem;
            text-align: left;
        }

        .ll-badges {
            display: flex; gap: .6rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap;
        }
        .ll-badge {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            color: rgba(255,230,230,.82);
            border-radius: 2rem;
            font-size: .72rem; font-weight: 600;
            padding: .3rem .8rem;
            display: flex; align-items: center; gap: .35rem;
        }
        .ll-badge i { font-size: .8rem; color: var(--gold-l); }

        /* ── Right panel ── */
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-form-wrap {
            width: 100%;
            max-width: 420px;
        }

        .lf-head { margin-bottom: 2rem; }
        .lf-welcome {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--cr);
            margin: 0 0 .3rem;
            letter-spacing: -.35px;
        }
        .lf-sub { font-size: .875rem; color: #7A6565; margin: 0; }

        /* Form elements */
        .lf-label {
            font-weight: 600; font-size: .82rem;
            color: #3A2020; margin-bottom: .38rem; display: block;
        }
        .lf-input-wrap { position: relative; }
        .lf-input-icon {
            position: absolute; left: .9rem; top: 50%; transform: translateY(-50%);
            color: #B09090; font-size: .95rem; pointer-events: none;
        }
        .lf-input {
            width: 100%;
            padding: .62rem .9rem .62rem 2.55rem;
            border: 1.5px solid #EDE2E2;
            border-radius: .6rem;
            font-size: .9rem;
            font-family: inherit;
            color: #2A1515;
            background: #fff;
            outline: none;
            transition: border .2s, box-shadow .2s;
        }
        .lf-input:focus {
            border-color: var(--cr);
            box-shadow: 0 0 0 3px rgba(139,26,26,.1);
        }
        .lf-input::placeholder { color: #B09090; }
        .lf-input.is-invalid { border-color: var(--cr-a); }

        .lf-eye {
            position: absolute; right: .9rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #B09090; font-size: .95rem; line-height: 1; padding: .2rem;
            transition: color .18s;
        }
        .lf-eye:hover { color: var(--cr); }

        .lf-submit {
            width: 100%;
            padding: .72rem;
            background: linear-gradient(135deg, var(--cr) 0%, #A02020 100%);
            border: none; border-radius: .65rem;
            color: #fff; font-size: .95rem; font-weight: 700;
            letter-spacing: .2px; cursor: pointer;
            font-family: inherit;
            box-shadow: 0 4px 14px rgba(139,26,26,.35);
            transition: all .22s ease;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .lf-submit:hover {
            background: linear-gradient(135deg, var(--cr-d) 0%, var(--cr) 100%);
            box-shadow: 0 6px 20px rgba(139,26,26,.42);
            transform: translateY(-1px);
        }
        .lf-submit:active { transform: translateY(0); }

        .lf-footer {
            text-align: center;
            font-size: .78rem;
            color: #9B7575;
            margin-top: 2rem;
        }
        .lf-footer a { color: var(--cr); font-weight: 600; }

        /* Error */
        .lf-alert {
            background: #FDDEDE; border-left: 4px solid var(--cr-a);
            border-radius: .6rem; padding: .75rem 1rem;
            font-size: .84rem; color: #6B1717;
            display: flex; align-items: center; gap: .65rem;
            margin-bottom: 1.25rem;
        }
        .invalid-feedback { font-size: .77rem; color: var(--cr-a); display: block; margin-top: .28rem; }

        /* Responsive */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .login-left { width: 100%; padding: 2.5rem 2rem; min-height: auto; }
            .ll-quote, .ll-badges { display: none; }
            .ll-title { font-size: 1.4rem; }
            .ll-emblem { width: 64px; height: 64px; font-size: 1.7rem; border-radius: 16px; margin-bottom: 1rem; }
            .lf-welcome { font-size: 1.35rem; }
        }
    </style>
</head>
<body>

    
    <div class="login-left">
        <div class="ll-inner">
            <div class="ll-emblem">
                <i class="bi bi-mortarboard-fill"></i>
            </div>

            <h1 class="ll-title"><?php echo e(config('app.name')); ?></h1>
            <p class="ll-tagline">Excellence in Education<br>Administration Portal</p>

            <div class="ll-divider"></div>

            <blockquote class="ll-quote">
                "Education is the most powerful weapon which you can use to change the world."
            </blockquote>

            <div class="ll-badges">
                <span class="ll-badge"><i class="bi bi-shield-check"></i> Secure</span>
                <span class="ll-badge"><i class="bi bi-lock-fill"></i> Role-Based</span>
                <span class="ll-badge"><i class="bi bi-speedometer2"></i> Fast</span>
            </div>
        </div>
    </div>

    
    <div class="login-right">
        <div class="login-form-wrap">

            <div class="lf-head">
                <h2 class="lf-welcome">Welcome back</h2>
                <p class="lf-sub">Sign in to your administrator account</p>
            </div>

            <?php if($errors->any()): ?>
                <div class="lf-alert">
                    <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                    <span><?php echo e($errors->first()); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('login')); ?>">
                <?php echo csrf_field(); ?>

                
                <div class="mb-3">
                    <label class="lf-label" for="email">Email Address</label>
                    <div class="lf-input-wrap">
                        <i class="bi bi-envelope lf-input-icon"></i>
                        <input type="email" id="email" name="email"
                               class="lf-input <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               value="<?php echo e(old('email')); ?>"
                               placeholder="admin@example.com"
                               autofocus required>
                    </div>
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="mb-4">
                    <label class="lf-label" for="password">Password</label>
                    <div class="lf-input-wrap">
                        <i class="bi bi-lock lf-input-icon"></i>
                        <input type="password" id="password" name="password"
                               class="lf-input"
                               placeholder="Enter your password"
                               required>
                        <button type="button" class="lf-eye" onclick="togglePwd()" tabindex="-1">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                               <?php echo e(old('remember') ? 'checked' : ''); ?>

                               style="border-color:#EDE2E2;cursor:pointer;">
                        <label class="form-check-label" for="remember"
                               style="font-size:.82rem;color:#5A3A3A;cursor:pointer;">
                            Keep me signed in
                        </label>
                    </div>
                </div>

                <button type="submit" class="lf-submit">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Sign In
                </button>
            </form>

            <div class="lf-footer">
                &copy; <?php echo e(date('Y')); ?> <?php echo e(config('app.name')); ?>. All rights reserved.
            </div>
        </div>
    </div>

<script>
function togglePwd() {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type       = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type       = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>

</body>
</html>
<?php /**PATH C:\projects\Food Management\resources\views/admin/auth/login.blade.php ENDPATH**/ ?>