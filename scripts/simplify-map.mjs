// Simplifies resources/images/europe.svg in place: node scripts/simplify-map.mjs resources/images/europe.svg resources/images/europe.svg 0.5
// Ramer–Douglas–Peucker per ring at the given tolerance (map units; the map renders 870 units wide), drops rings outside the rendered viewBox, rounds to 0.1.
import { readFileSync, writeFileSync } from 'node:fs';

const [src, out, tolArg] = process.argv.slice(2);
const TOL = Number(tolArg ?? 0.35);
const VIEW = { x0: 130, y0: 110, x1: 1000, y1: 670 };

const parseRings = (d) => {
    const tokens = d.match(/[a-zA-Z]|-?\d*\.?\d+(?:e-?\d+)?/g);
    const rings = [];
    let ring = null;
    let start = [0, 0];
    let cmd = null;
    let x = 0;
    let y = 0;
    let i = 0;
    while (i < tokens.length) {
        const t = tokens[i];
        if (/[a-zA-Z]/.test(t)) { cmd = t; i++; if (cmd === 'z' || cmd === 'Z') { if (ring) rings.push(ring); ring = null; [x, y] = start; } continue; }
        const a = Number(tokens[i]);
        const b = Number(tokens[i + 1]);
        i += 2;
        if (cmd === 'M' || cmd === 'm') {
            if (ring) rings.push(ring);
            x = cmd === 'M' ? a : x + a;
            y = cmd === 'M' ? b : y + b;
            ring = [[x, y]];
            start = [x, y];
            cmd = cmd === 'M' ? 'L' : 'l';
        } else if (cmd === 'L' || cmd === 'l') {
            x = cmd === 'L' ? a : x + a;
            y = cmd === 'L' ? b : y + b;
            ring.push([x, y]);
        } else {
            throw new Error('unsupported command ' + cmd);
        }
    }
    if (ring) rings.push(ring);
    return rings;
};

const perp = ([px, py], [ax, ay], [bx, by]) => {
    const dx = bx - ax;
    const dy = by - ay;
    const len = Math.hypot(dx, dy);
    return len === 0 ? Math.hypot(px - ax, py - ay) : Math.abs(dy * px - dx * py + bx * ay - by * ax) / len;
};

const rdp = (pts, tol) => {
    if (pts.length < 3) return pts;
    let max = 0;
    let idx = 0;
    for (let i = 1; i < pts.length - 1; i++) {
        const d = perp(pts[i], pts[0], pts[pts.length - 1]);
        if (d > max) { max = d; idx = i; }
    }
    if (max <= tol) return [pts[0], pts[pts.length - 1]];
    return [...rdp(pts.slice(0, idx + 1), tol).slice(0, -1), ...rdp(pts.slice(idx), tol)];
};

const simplifyRing = (ring) => {
    // Split the closed ring at its farthest point so RDP keeps the shape.
    let far = 0;
    let fd = 0;
    ring.forEach((p, i) => { const d = Math.hypot(p[0] - ring[0][0], p[1] - ring[0][1]); if (d > fd) { fd = d; far = i; } });
    const a = rdp(ring.slice(0, far + 1), TOL);
    const b = rdp([...ring.slice(far), ring[0]], TOL);
    return [...a.slice(0, -1), ...b.slice(0, -1)];
};

const outside = (ring) => ring.every(([x, y]) => x < VIEW.x0) || ring.every(([x, y]) => x > VIEW.x1) || ring.every(([, y]) => y < VIEW.y0) || ring.every(([, y]) => y > VIEW.y1);

const r1 = (n) => {
    const s = (Math.round(n * 10) / 10).toFixed(1).replace(/\.0$/, '');
    return s.replace(/^(-?)0\./, '$1.');
};

const join = (nums) => nums.reduce((s, n, i) => {
    if (i === 0) return n;
    const prev = nums[i - 1];
    const tight = n.startsWith('-') || (n.startsWith('.') && prev.includes('.'));
    return s + (tight ? '' : ' ') + n;
}, '');

const encode = (rings) => rings.map((ring) => {
    let px = Math.round(ring[0][0] * 10) / 10;
    let py = Math.round(ring[0][1] * 10) / 10;
    const nums = [];
    for (const [x, y] of ring.slice(1)) {
        const rx = Math.round(x * 10) / 10;
        const ry = Math.round(y * 10) / 10;
        const dx = Math.round((rx - px) * 10) / 10;
        const dy = Math.round((ry - py) * 10) / 10;
        if (dx === 0 && dy === 0) continue;
        nums.push(r1(dx), r1(dy));
        px = rx;
        py = ry;
    }
    return `M${join([r1(Math.round(ring[0][0] * 10) / 10), r1(Math.round(ring[0][1] * 10) / 10)])}l${join(nums)}z`;
}).join('');

const svg = readFileSync(src, 'utf8');
let before = 0;
let after = 0;
let droppedRings = 0;
let droppedPaths = 0;
const result = svg.replace(/<path d="([^"]+)"\s+id="([A-Z]{2})"\s+name="([^"]*)">\s*<\/path>/g, (all, d, id, name) => {
    before += d.length;
    const rings = parseRings(d).filter((ring) => { const keep = !outside(ring); if (!keep) droppedRings++; return keep; }).map(simplifyRing).filter((ring) => ring.length >= 3);
    if (!rings.length) { droppedPaths++; return ''; }
    const nd = encode(rings);
    after += nd.length;
    return `<path d="${nd}" id="${id}" name="${name}"></path>`;
}).replace(/>\s+</g, '><');
writeFileSync(out, result);
console.log({ tol: TOL, before, after, droppedRings, droppedPaths, file: result.length });
