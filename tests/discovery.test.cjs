const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const vm = require('node:vm');
const source = readFileSync(require('node:path').join(__dirname, '../assets/storev1-discovery.js'), 'utf8');
function setup({overflow = true, reduced = false, desktop = true} = {}) {
  function node() {
    const handlers = {};
    return {handlers, hidden:true, disabled:false, classList:{add(){},remove(){}},
      addEventListener(type, fn) {(handlers[type] ||= []).push(fn);},
      fire(type, event = {}) {(handlers[type] || []).forEach(fn => fn(event));},
      setAttribute(){}, setPointerCapture(){},hasPointerCapture(){return true;},releasePointerCapture(){}};
  }
  const track = Object.assign(node(), {scrollWidth:overflow ? 900 : 400, clientWidth:400, scrollLeft:0,
    scrollBy({left}) {this.scrollLeft = Math.max(0, Math.min(this.scrollWidth-this.clientWidth, this.scrollLeft+left));}});
  const prev = node(), next = node(), rail = node(), document = node(), window = node();
  rail.querySelector = selector => ({'.loja1-menu':track,'[data-category-prev]':prev,'[data-category-next]':next})[selector];
  document.querySelectorAll = () => [rail]; document.readyState = 'complete';
  const frames = new Map(), timers = new Map(); let id = 0;
  vm.runInNewContext(source, {document,window,matchMedia:q=>Object.assign(node(),{matches:q.includes('reduced') ? reduced : desktop}),
    ResizeObserver:class {observe(){}},
    requestAnimationFrame:fn=>{frames.set(++id,fn);return id;},cancelAnimationFrame:key=>frames.delete(key),
    setTimeout:fn=>{timers.set(++id,fn);return id;}, clearTimeout:key=>timers.delete(key)});
  const flushTimers = () => {const work=[...timers.values()];timers.clear();work.forEach(fn=>fn());};
  const step = now => {const work=[...frames.values()];frames.clear();work.forEach(fn=>fn(now));};
  return {track,prev,next,rail,document,flushTimers,step,frames};
}
test('initial hint reaches the end and returns to the original position once', () => {
  const x = setup(); x.flushTimers(); x.step(0); x.step(1100);
  assert.equal(x.track.scrollLeft,500);
  x.step(1350); x.step(2450);
  assert.equal(x.track.scrollLeft,0); assert.equal(x.frames.size,0);
});
test('does not animate with reduced motion, on mobile, or without overflow', () => {
  for (const options of [{reduced:true},{desktop:false},{overflow:false}]) {
    const x=setup(options);x.flushTimers();assert.equal(x.frames.size,0);
  }
});
test('user interaction cancels the hint immediately without resetting their position', () => {
  for (const type of ['pointerdown','wheel','focusin','keydown']) {
    const x=setup();x.flushTimers();x.step(0);x.step(550);
    const position=x.track.scrollLeft;x.rail.fire(type);x.step(2450);
    assert.equal(x.track.scrollLeft,position);assert.equal(x.frames.size,0);
  }
});
test('arrows reflect overflow and keyboard changes category scroll', () => {
  const x=setup();assert.equal(x.prev.hidden,false);assert.equal(x.prev.disabled,true);
  x.next.fire('click');assert.equal(x.track.scrollLeft,280);
  x.track.fire('keydown',{key:'ArrowRight',preventDefault(){}});x.track.fire('scroll');
  assert.equal(x.track.scrollLeft,500);assert.equal(x.next.disabled,true);
  assert.equal(setup({overflow:false}).next.hidden,true);
});
test('mouse drag scrolls without navigating; a simple click is preserved', () => {
  const x=setup();let prevented=false;
  const event={pointerType:'mouse',button:0,isPrimary:true,pointerId:1,clientX:300};
  x.track.fire('pointerdown',event);x.track.fire('pointermove',{...event,clientX:100});
  x.track.fire('pointerup',event);
  x.track.fire('click',{preventDefault(){prevented=true;},stopPropagation(){}});
  assert.equal(x.track.scrollLeft,200);assert.equal(prevented,true);
  x.flushTimers();prevented=false;
  x.track.fire('pointerdown',event);x.track.fire('pointerup',event);
  x.track.fire('click',{preventDefault(){prevented=true;},stopPropagation(){}});
  assert.equal(prevented,false);
});
test('touch uses native scroll instead of mouse pointer capture', () => {
  const x=setup();const event={pointerType:'touch',button:0,isPrimary:true,pointerId:1,clientX:300};
  x.track.fire('pointerdown',event);x.track.fire('pointermove',{...event,clientX:100});
  assert.equal(x.track.scrollLeft,0);
});
