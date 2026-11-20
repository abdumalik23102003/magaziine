(function initProducePhysics() {
  const container = document.getElementById('physics-sphere');
  if (!container) return;

  const items = [
    { 
      el: document.getElementById('fruit-item-0'), 
      name: 'avocado',
      x: 95, y: 125, vx: 0.6, vy: -0.4, 
      radius: 36, mass: 1.05, rot: -8, vRot: 0.2,
      orbitAngle: 0.5, orbitRadius: 65, orbitSpeed: 0.007,
      scaleX: 1, scaleY: 1,
      glowColor: 'rgba(0, 245, 155, 0.7)'
    },
    { 
      el: document.getElementById('fruit-item-1'), 
      name: 'broccoli',
      x: 250, y: 110, vx: -0.5, vy: 0.6, 
      radius: 34, mass: 0.95, rot: 15, vRot: -0.3,
      orbitAngle: 2.1, orbitRadius: 78, orbitSpeed: 0.006,
      scaleX: 1, scaleY: 1,
      glowColor: 'rgba(16, 185, 129, 0.7)'
    },
    { 
      el: document.getElementById('fruit-item-2'), 
      name: 'orange',
      x: 180, y: 195, vx: 0.4, vy: -0.8, 
      radius: 40, mass: 1.15, rot: -6, vRot: 0.25,
      orbitAngle: 3.8, orbitRadius: 72, orbitSpeed: 0.008,
      scaleX: 1, scaleY: 1,
      glowColor: 'rgba(249, 115, 22, 0.8)'
    },
    { 
      el: document.getElementById('fruit-item-3'), 
      name: 'strawberry',
      x: 120, y: 255, vx: -0.4, vy: -0.5, 
      radius: 32, mass: 0.85, rot: 10, vRot: -0.35,
      orbitAngle: 5.2, orbitRadius: 82, orbitSpeed: 0.009,
      scaleX: 1, scaleY: 1,
      glowColor: 'rgba(244, 63, 94, 0.8)'
    }
  ];

  let activeDrag = null;
  let dragOffset = { x: 0, y: 0 };
  let pointerHistory = [];

  function getContainerMetrics() {
    const rect = container.getBoundingClientRect();
    const cx = rect.width / 2;
    const cy = rect.height / 2;
    const radius = Math.min(cx, cy);
    return { rect, cx, cy, radius };
  }

  function spawnImpactSpark(cx, cy, color) {
    const spark = document.createElement('div');
    spark.className = 'impact-spark';
    spark.style.left = `${cx}px`;
    spark.style.top = `${cy}px`;
    spark.style.width = '55px';
    spark.style.height = '55px';
    spark.style.border = `2px solid ${color || '#00f59b'}`;
    spark.style.boxShadow = `0 0 16px ${color || '#00f59b'}`;
    spark.style.background = `radial-gradient(circle, ${color || '#00f59b'}44 0%, transparent 70%)`;
    container.appendChild(spark);
    setTimeout(() => {
      if (spark && spark.parentNode) spark.remove();
    }, 550);
  }

  // Bind pointer & touch events
  items.forEach(item => {
    if (!item.el) return;

    function startDrag(clientX, clientY) {
      const { rect } = getContainerMetrics();
      const pointerX = clientX - rect.left;
      const pointerY = clientY - rect.top;

      activeDrag = item;
      dragOffset.x = item.x - pointerX;
      dragOffset.y = item.y - pointerY;

      item.vx = 0;
      item.vy = 0;
      item.vRot = 0;
      item.scaleX = 1.18;
      item.scaleY = 1.18;
      item.el.classList.add('is-dragging');

      pointerHistory = [{ x: pointerX, y: pointerY, time: performance.now() }];
    }

    item.el.addEventListener('mousedown', (e) => {
      e.preventDefault();
      startDrag(e.clientX, e.clientY);
    });

    item.el.addEventListener('touchstart', (e) => {
      if (e.touches.length > 0) {
        startDrag(e.touches[0].clientX, e.touches[0].clientY);
      }
    }, { passive: true });
  });

  function onPointerMove(clientX, clientY) {
    if (!activeDrag) return;
    const { rect, cx, cy, radius } = getContainerMetrics();
    const pointerX = clientX - rect.left;
    const pointerY = clientY - rect.top;

    let targetX = pointerX + dragOffset.x;
    let targetY = pointerY + dragOffset.y;

    // Constrain inside circle boundary while dragging
    const dx = targetX - cx;
    const dy = targetY - cy;
    const dist = Math.hypot(dx, dy);
    const maxDist = Math.max(10, radius - activeDrag.radius - 6);

    if (dist > maxDist) {
      const angle = Math.atan2(dy, dx);
      targetX = cx + Math.cos(angle) * maxDist;
      targetY = cy + Math.sin(angle) * maxDist;
    }

    activeDrag.x = targetX;
    activeDrag.y = targetY;

    const now = performance.now();
    pointerHistory.push({ x: pointerX, y: pointerY, time: now });
    // Keep last 120ms of motion
    pointerHistory = pointerHistory.filter(p => now - p.time <= 120);
  }

  function endDrag() {
    if (!activeDrag) return;

    if (pointerHistory.length >= 2) {
      const oldest = pointerHistory[0];
      const newest = pointerHistory[pointerHistory.length - 1];
      const dt = Math.max(10, newest.time - oldest.time);
      
      let vx = ((newest.x - oldest.x) / dt) * 22;
      let vy = ((newest.y - oldest.y) / dt) * 22;

      // Cap maximum fling speed for balanced control
      const speed = Math.hypot(vx, vy);
      const maxSpeed = 28;
      if (speed > maxSpeed) {
        vx = (vx / speed) * maxSpeed;
        vy = (vy / speed) * maxSpeed;
      }

      activeDrag.vx = vx;
      activeDrag.vy = vy;
      activeDrag.vRot = (Math.random() - 0.5) * (Math.min(speed, 20) * 0.55 + 2.5);
    } else {
      activeDrag.vx = (Math.random() - 0.5) * 2;
      activeDrag.vy = (Math.random() - 0.5) * 2;
    }

    activeDrag.el.classList.remove('is-dragging');
    activeDrag = null;
    pointerHistory = [];
  }

  window.addEventListener('mousemove', (e) => onPointerMove(e.clientX, e.clientY));
  window.addEventListener('mouseup', endDrag);

  window.addEventListener('touchmove', (e) => {
    if (e.touches.length > 0) {
      onPointerMove(e.touches[0].clientX, e.touches[0].clientY);
    }
  }, { passive: true });
  window.addEventListener('touchend', endDrag);
  window.addEventListener('touchcancel', endDrag);

  // Physics and Animation simulation loop
  let lastTime = performance.now();
  function physicsLoop(now) {
    const dt = Math.min((now - lastTime) / 1000, 0.04);
    lastTime = now;

    const { cx, cy, radius } = getContainerMetrics();
    const damping = 0.985; // gentle friction & air resistance
    const restitution = 0.82; // elastic bounce factor
    const ambientThreshold = 1.35; // velocity threshold for gentle drift

    items.forEach((item, index) => {
      if (item !== activeDrag) {
        const curSpeed = Math.hypot(item.vx, item.vy);

        if (curSpeed < ambientThreshold) {
          // Normal state: Idle gentle orbital drift and natural floating bobbing
          item.orbitAngle += item.orbitSpeed;
          const targetX = cx + Math.cos(item.orbitAngle + index * (Math.PI / 2)) * item.orbitRadius;
          const targetY = cy + Math.sin(item.orbitAngle + index * (Math.PI / 2)) * (item.orbitRadius * 0.75) + Math.sin(now * 0.0025 + index) * 6;

          // Smoothly attract towards orbit location
          item.vx += (targetX - item.x) * 0.035;
          item.vy += (targetY - item.y) * 0.035;
          item.vRot += (Math.sin(now * 0.002 + index) * 0.4 - item.vRot) * 0.05;
        }

        // Apply velocity & friction
        item.x += item.vx;
        item.y += item.vy;

        item.vx *= damping;
        item.vy *= damping;

        // Rotational motion
        item.rot += item.vRot;
        item.vRot *= 0.988;

        // Smooth squash/stretch return to 1
        item.scaleX += (1 - item.scaleX) * 0.12;
        item.scaleY += (1 - item.scaleY) * 0.12;

        // Circular boundary elastic collision
        const dx = item.x - cx;
        const dy = item.y - cy;
        const dist = Math.hypot(dx, dy);
        const maxDist = Math.max(10, radius - item.radius - 6);

        if (dist > maxDist) {
          const normalX = dx / (dist || 1);
          const normalY = dy / (dist || 1);

          // Position clamping
          item.x = cx + normalX * maxDist;
          item.y = cy + normalY * maxDist;

          // Velocity vector reflection on surface normal
          const dot = item.vx * normalX + item.vy * normalY;
          if (dot > 0) {
            item.vx = (item.vx - 2 * dot * normalX) * restitution;
            item.vy = (item.vy - 2 * dot * normalY) * restitution;
            item.vRot += (Math.random() - 0.5) * 4.5;

            // Wall bounce impact effect: Squash-and-stretch & spark
            const impactVelocity = Math.abs(dot);
            if (impactVelocity > 2.8) {
              item.scaleX = 0.84;
              item.scaleY = 1.22;
              spawnImpactSpark(item.x, item.y, item.glowColor);
            }
          }
        }
      } else {
        // Dragging state squash/stretch recovery
        item.scaleX = 1.15;
        item.scaleY = 1.15;
      }

      // Render fruit position and rotation
      if (item.el) {
        const renderX = item.x - item.radius;
        const renderY = item.y - item.radius;
        item.el.style.transform = `translate3d(${renderX}px, ${renderY}px, 0) rotate(${item.rot}deg) scale(${item.scaleX}, ${item.scaleY})`;
      }
    });

    requestAnimationFrame(physicsLoop);
  }
  requestAnimationFrame(physicsLoop);
})();
