<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title'); ?> — <?php echo e(config('app.name')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:      #ad3939;
            --primary-dark: #6D1313;
            --primary-mid:  #A02020;
            --accent:       #c04d40;
            --gold:         #C9A227;
            --bg:           #F8F3F3;
            --border:       #EDE2E2;
            --text:         #2A1515;
            --text-2:       #5C3A3A;
            --text-muted:   #9B7575;
            --surface:      #FFFFFF;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .err-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
            text-align: center;
        }

        .err-card {
            background: var(--surface);
            border-radius: 1.25rem;
            box-shadow: 0 8px 40px rgba(139,26,26,.13), 0 2px 8px rgba(0,0,0,.06);
            padding: 3rem 2.5rem 2.5rem;
            max-width: 480px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .err-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-dark), var(--primary), var(--accent));
        }

        .err-icon-wrap {
            width: 88px; height: 88px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.4rem;
            position: relative;
        }

        .err-code {
            font-size: 5.5rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -3px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: .5rem;
        }

        .err-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: .65rem;
        }

        .err-desc {
            font-size: .9rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .err-actions {
            display: flex;
            gap: .75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary-err {
            display: inline-flex; align-items: center; gap: .45rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-mid) 100%);
            color: #fff;
            border: none;
            border-radius: .6rem;
            padding: .65rem 1.4rem;
            font-size: .875rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .18s, transform .15s;
            box-shadow: 0 3px 12px rgba(139,26,26,.28);
        }
        .btn-primary-err:hover { opacity: .88; transform: translateY(-1px); color: #fff; }

        .btn-ghost-err {
            display: inline-flex; align-items: center; gap: .45rem;
            background: transparent;
            color: var(--primary);
            border: 1.5px solid var(--border);
            border-radius: .6rem;
            padding: .65rem 1.4rem;
            font-size: .875rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: border-color .18s, background .18s;
        }
        .btn-ghost-err:hover { border-color: var(--primary); background: rgba(173,57,57,.05); color: var(--primary); }

        .err-app-name {
            margin-top: 2rem;
            font-size: .78rem;
            color: var(--text-muted);
            letter-spacing: .3px;
        }

        @media (max-width: 480px) {
            .err-card { padding: 2.25rem 1.5rem 2rem; }
            .err-code { font-size: 4.5rem; }
        }
    </style>
    <?php echo $__env->yieldPushContent('extra-style'); ?>
</head>
<body>
<div class="err-page">
    <div class="err-card">
        <?php echo $__env->yieldContent('body'); ?>
    </div>
    <div class="err-app-name"><?php echo e(config('app.name')); ?></div>
</div>
</body>
</html>
<?php /**PATH C:\projects\Gurukul Parivar\resources\views/errors/layout.blade.php ENDPATH**/ ?>