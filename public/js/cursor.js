(function() {
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (prefersReducedMotion) return;

const canvas = document.getElementById('water-cursor-canvas');
if (!canvas) return;

const ctx = canvas.getContext('2d', { alpha: true });
let width = window.innerWidth;
let height = window.innerHeight;
let dpr = window.devicePixelRatio || 1;

function resize() {
    width = window.innerWidth;
    height = window.innerHeight;
    dpr = window.devicePixelRatio || 1;
    canvas.width = Math.floor(width * dpr);
    canvas.height = Math.floor(height * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
}
window.addEventListener('resize', resize, { passive: true });
resize();

let mouse = { x: -200, y: -200, isMoving: false };
let smoothMouse = { x: -200, y: -200 };

const points = [];
const MAX_POINTS = 32;

const droplets = [];
const MAX_DROPLETS = 65;

const palette = [
    { r: 0,   g: 245, b: 155 },
    { r: 16,  g: 185, b: 129 },
    { r: 45,  g: 212, b: 191 },
    { r: 210, g: 255, b: 0 }
];

class Droplet {
    constructor(x, y, radius, vx, vy) {
        this.x = x;
        this.y = y;
        this.vx = vx;
        this.vy = vy;
        this.radius = radius;
        this.initialRadius = radius;
        this.life = 1.0;
        this.decay = 0.024 + Math.random() * 0.02;
        this.color = palette[Math.floor(Math.random() * palette.length)];
    }

    update() {
        this.x += this.vx;
        this.y += this.vy;
        this.vx *= 0.94;
        this.vy = this.vy * 0.94 + 0.06;
        this.radius = this.initialRadius * Math.max(this.life, 0.1);
        this.life -= this.decay;
    }

    draw(c) {
        if (this.life <= 0) return;
        const alpha = Math.max(0, this.life * 0.45);
        const { r, g, b } = this.color;
        const grad = c.createRadialGradient(this.x, this.y, 0, this.x, this.y, this.radius * 2.2);
        grad.addColorStop(0, `rgba(${r}, ${g}, ${b}, ${alpha})`);
        grad.addColorStop(0.5, `rgba(${r}, ${g}, ${b}, ${alpha * 0.4})`);
        grad.addColorStop(1, `rgba(${r}, ${g}, ${b}, 0)`);

        c.fillStyle = grad;
        c.beginPath();
        c.arc(this.x, this.y, this.radius * 2.2, 0, Math.PI * 2);
        c.fill();
    }
}

window.addEventListener('mousemove', (e) => {
    mouse.x = e.clientX;
    mouse.y = e.clientY;
    mouse.isMoving = true;

    if (smoothMouse.x < 0) {
        smoothMouse.x = mouse.x;
        smoothMouse.y = mouse.y;
    }
}, { passive: true });

window.addEventListener('mouseleave', () => {
    mouse.x = -200;
    mouse.y = -200;
    mouse.isMoving = false;
});

function loop() {
    ctx.clearRect(0, 0, width, height);

    if (mouse.x > -50 && mouse.y > -50) {
        const dx = mouse.x - smoothMouse.x;
        const dy = mouse.y - smoothMouse.y;
        const speed = Math.hypot(dx, dy);

        const lerpFactor = Math.min(0.42, 0.28 + speed * 0.005);
        smoothMouse.x += dx * lerpFactor;
        smoothMouse.y += dy * lerpFactor;

        points.push({ x: smoothMouse.x, y: smoothMouse.y, age: 1.0 });
        if (points.length > MAX_POINTS) {
            points.shift();
        }

        if (speed > 1.8 && droplets.length < MAX_DROPLETS) {
            const count = Math.min(3, Math.floor(speed / 4) + 1);
            for (let i = 0; i < count; i++) {
                const angle = Math.random() * Math.PI * 2;
                const spd = Math.random() * (speed * 0.15) + 0.4;
                const r = Math.min(3 + speed * 0.28, 11);
                droplets.push(new Droplet(
                    smoothMouse.x + (Math.random() - 0.5) * 6,
                    smoothMouse.y + (Math.random() - 0.5) * 6,
                    r,
                    Math.cos(angle) * spd,
                    Math.sin(angle) * spd
                ));
            }
        }
    } else {
        if (points.length > 0) points.shift();
    }

    ctx.save();
    ctx.globalCompositeOperation = 'screen';

    if (points.length > 2) {
        for (let i = 1; i < points.length; i++) {
            const p0 = points[i - 1];
            const p1 = points[i];
            const factor = i / points.length;
            const alpha = Math.pow(factor, 1.4) * 0.45;

            ctx.beginPath();
            ctx.moveTo(p0.x, p0.y);
            ctx.lineTo(p1.x, p1.y);
            ctx.strokeStyle = `rgba(0, 245, 155, ${alpha})`;
            ctx.lineWidth = factor * 10 + 2;
            ctx.lineCap = 'round';
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(p0.x, p0.y);
            ctx.lineTo(p1.x, p1.y);
            ctx.strokeStyle = `rgba(255, 255, 255, ${alpha * 0.6})`;
            ctx.lineWidth = factor * 3 + 1;
            ctx.stroke();
        }
    }

    for (let i = droplets.length - 1; i >= 0; i--) {
        const d = droplets[i];
        d.update();
        d.draw(ctx);
        if (d.life <= 0) droplets.splice(i, 1);
    }

    if (mouse.x > -50 && mouse.y > -50) {
        const glowGrad = ctx.createRadialGradient(smoothMouse.x, smoothMouse.y, 0, smoothMouse.x, smoothMouse.y, 16);
        glowGrad.addColorStop(0, 'rgba(255, 255, 255, 0.85)');
        glowGrad.addColorStop(0.35, 'rgba(0, 245, 155, 0.45)');
        glowGrad.addColorStop(0.7, 'rgba(16, 185, 129, 0.2)');
        glowGrad.addColorStop(1, 'rgba(0, 245, 155, 0.0)');

        ctx.fillStyle = glowGrad;
        ctx.beginPath();
        ctx.arc(smoothMouse.x, smoothMouse.y, 16, 0, Math.PI * 2);
        ctx.fill();

        ctx.beginPath();
        ctx.arc(smoothMouse.x - 2, smoothMouse.y - 2, 2.8, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(255, 255, 255, 0.95)';
        ctx.fill();
    }

    ctx.restore();
    requestAnimationFrame(loop);
}
requestAnimationFrame(loop);
})();
