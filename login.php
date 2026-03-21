<?php
session_start();
$error_msg = '';

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid') {
        $error_msg = 'Invalid username or password.';
    }
    elseif ($_GET['error'] === 'empty') {
        $error_msg = 'Please enter both username and password.';
    }
    elseif ($_GET['error'] === 'unauthorized') {
        $error_msg = 'Please log in to access the dashboard.';
    }
    elseif ($_GET['error'] === 'pending') {
        $error_msg = 'Your account is pending approval by an administrator.';
    }
    elseif ($_GET['error'] === 'rejected') {
        $error_msg = 'Your account has been rejected by an administrator.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FleetVision</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #050b18;
            overflow: hidden;
            position: relative;
        }

        /* ── Animated canvas background ── */
        #bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
        }

        /* ── Radial glow blobs ── */
        .glow-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.18;
            z-index: 0;
            animation: blobDrift 18s ease-in-out infinite alternate;
        }
        .glow-blob.b1 { width: 600px; height: 600px; background: #3b82f6; top: -150px; left: -150px; animation-duration: 20s; }
        .glow-blob.b2 { width: 500px; height: 500px; background: #6366f1; bottom: -150px; right: -100px; animation-duration: 16s; animation-delay: -6s; }
        .glow-blob.b3 { width: 350px; height: 350px; background: #0ea5e9; top: 40%; left: 40%; animation-duration: 24s; animation-delay: -12s; }

        @keyframes blobDrift {
            0%   { transform: translate(0, 0) scale(1); }
            50%  { transform: translate(40px, -30px) scale(1.08); }
            100% { transform: translate(-30px, 40px) scale(0.95); }
        }

        /* ── Card ── */
        .auth-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 1rem;
            animation: cardIn 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(28px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .auth-card {
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 20px;
            padding: 2.5rem 2.25rem;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.04) inset,
                0 32px 64px rgba(0,0,0,0.5),
                0 0 80px rgba(59,130,246,0.08);
        }

        /* ── Header ── */
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo {
            height: 68px;
            width: auto;
            object-fit: contain;
            margin-bottom: 1rem;
            filter: drop-shadow(0 0 18px rgba(99,102,241,0.5));
            animation: logoPulse 3s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { filter: drop-shadow(0 0 14px rgba(99,102,241,0.4)); }
            50%       { filter: drop-shadow(0 0 28px rgba(99,102,241,0.7)); }
        }

        .auth-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: -0.02em;
        }

        .auth-subtitle {
            margin-top: 0.35rem;
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 400;
        }

        /* ── Alert ── */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
        }

        /* ── Form ── */
        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: #94a3b8;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #4b5563;
            pointer-events: none;
            font-size: 1rem;
            transition: color 0.2s;
        }

        .form-input {
            width: 100%;
            padding: 0.78rem 0.9rem 0.78rem 2.5rem;
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            color: #e2e8f0;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
            outline: none;
        }

        .form-input::placeholder { color: #475569; }

        .form-input:focus {
            border-color: #6366f1;
            background: rgba(30, 41, 59, 0.95);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18), 0 0 20px rgba(99,102,241,0.08);
        }

        .form-input:focus + .input-icon,
        .input-wrap:focus-within .input-icon { color: #818cf8; }

        /* ── Submit button ── */
        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            margin-top: 0.5rem;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: transform 0.18s, box-shadow 0.18s;
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.4);
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), rgba(255,255,255,0));
            opacity: 0;
            transition: opacity 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(79,70,229,0.55);
        }

        .btn-submit:hover::before { opacity: 1; }
        .btn-submit:active { transform: translateY(0); }

        /* ── Divider line at bottom ── */
        .card-footer {
            margin-top: 1.6rem;
            text-align: center;
            font-size: 0.78rem;
            color: #334155;
        }
    </style>
</head>
<body>

    <!-- Blobs -->
    <div class="glow-blob b1"></div>
    <div class="glow-blob b2"></div>
    <div class="glow-blob b3"></div>

    <!-- Particle canvas -->
    <canvas id="bg-canvas"></canvas>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <img src="FleetVision Logo.png" alt="FleetVision Logo" class="auth-logo">
                <h1 class="auth-title">Welcome Back To Your Fleet</h1>
                <p class="auth-subtitle">Sign in to continue to your dashboard</p>
            </div>

            <?php if ($error_msg): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="auth.php" autocomplete="on">
                <div class="form-group">
                    <label class="form-label" for="username">Username or Email</label>
                    <div class="input-wrap">
                        <input type="text" id="username" name="username" class="form-input"
                               placeholder="Enter your username or email"
                               required autocomplete="username">
                        <span class="input-icon">👤</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" class="form-input"
                               placeholder="Enter your password"
                               required autocomplete="current-password">
                        <span class="input-icon">🔒</span>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Sign In →</button>
            </form>

            <div class="card-footer">© 2026 FleetVision. All rights reserved.</div>
        </div>
    </div>

    <script>
        // Animated particle network background
        const canvas = document.getElementById('bg-canvas');
        const ctx = canvas.getContext('2d');
        let W, H, particles;

        const PARTICLE_COUNT = 80;
        const CONNECTION_DIST = 140;
        const SPEED = 0.35;

        function resize() {
            W = canvas.width  = window.innerWidth;
            H = canvas.height = window.innerHeight;
        }

        function randomParticle() {
            return {
                x: Math.random() * W,
                y: Math.random() * H,
                vx: (Math.random() - 0.5) * SPEED,
                vy: (Math.random() - 0.5) * SPEED,
                r: Math.random() * 1.8 + 0.6,
                alpha: Math.random() * 0.5 + 0.2
            };
        }

        function init() {
            resize();
            particles = Array.from({ length: PARTICLE_COUNT }, randomParticle);
        }

        function draw() {
            ctx.clearRect(0, 0, W, H);

            // Move & wrap
            for (const p of particles) {
                p.x += p.vx;
                p.y += p.vy;
                if (p.x < 0) p.x = W;
                if (p.x > W) p.x = 0;
                if (p.y < 0) p.y = H;
                if (p.y > H) p.y = 0;
            }

            // Draw connections
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < CONNECTION_DIST) {
                        const opacity = (1 - dist / CONNECTION_DIST) * 0.25;
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(99,102,241,${opacity})`;
                        ctx.lineWidth = 0.8;
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                    }
                }
            }

            // Draw dots
            for (const p of particles) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(139,148,255,${p.alpha})`;
                ctx.fill();
            }

            requestAnimationFrame(draw);
        }

        window.addEventListener('resize', () => { resize(); });
        init();
        draw();
    </script>
</body>
</html>
