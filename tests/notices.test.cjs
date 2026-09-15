const {test} = require('node:test');
const assert = require('node:assert/strict');
const {runInNewContext} = require('node:vm');
const {readFileSync} = require('node:fs');
function setup(tag = 'div') {
  function element(tag) {
    return {tag,children:[],style:{},offsetWidth:330,events:{},classList:{add(){}},
      appendChild(child){this.children.push(child);child.parentElement=this;},
      addEventListener(name,fn){this.events[name]=fn;},setAttribute(){},
      setPointerCapture(){},matches(s){return s==='ul,ol' && ['ul','ol'].includes(tag);},
      contains(node){return this.children.includes(node);},remove(){this.removed=true;}};
  }
  const portal=element('div'),notice=element(tag),body=element('body'),dialog=element('dialog');
  let open=true;
  const doc={body,activeElement:null,createElement:element,getElementById:()=>portal,
    querySelectorAll:s=>s==='dialog[open]'?(open?[dialog]:[]):[notice]};
  let sync;
  const timers = new Map(); let nextTimer=0;
  runInNewContext(readFileSync(require.resolve('../assets/storev1-notices.js'),'utf8'),{
    document:doc,MutationObserver:class{constructor(fn){sync=fn}observe(){}},
    setTimeout(fn,ms){timers.set(++nextTimer,{fn,ms});return nextTimer;},
    clearTimeout(id){timers.delete(id);}
  });
  const button=notice.children[0].children[0];
  const down=(x=100,y=50,interactive=false)=>notice.events.pointerdown({isPrimary:true,button:0,pointerId:1,clientX:x,clientY:y,target:{closest:()=>interactive?button:null}});
  const up=(x,y=50)=>notice.events.pointerup({pointerId:1,clientX:x,clientY:y});
  return {portal,notice,button,body,dialog,down,up,sync,timers,closeDialog(){open=false;sync();}};
}
test('real close button works inside modal, and portal follows modal close',()=>{
  const x=setup();assert.equal(x.portal.parentElement,x.dialog);
  x.sync();assert.equal(x.notice.children.length,1);
  x.button.events.click({preventDefault(){},stopPropagation(){}});
  assert.equal(x.notice.removed,true);x.closeDialog();assert.equal(x.portal.parentElement,x.body);
});
test('swipe dismisses both directions but not vertical scrolling, short drags or control clicks',()=>{
  for(const end of [20,180]){const x=setup();x.down();x.up(end);assert.equal(x.notice.removed,true);}
  for(const [end,y,interactive] of [[120,50,false],[180,250,false],[180,50,true]]){
    const x=setup();x.down(100,50,interactive);x.up(end,y);assert.notEqual(x.notice.removed,true);
  }
});
test('error lists have valid children, canceled gestures do not dismiss',()=>{
  const x=setup('ul');assert.equal(x.notice.children[0].tag,'li');
  x.down();x.notice.events.pointercancel();x.up(220);assert.notEqual(x.notice.removed,true);
});
test('automatically dismisses after six seconds and clears timer on manual close',()=>{
  const x=setup();assert.equal(x.timers.size,1);
  const timer=[...x.timers.values()][0];assert.equal(timer.ms,6000);
  x.sync();assert.equal(x.timers.size,1);
  timer.fn();assert.equal(x.notice.removed,true);assert.equal(x.timers.size,0);
  const y=setup();y.button.events.click({preventDefault(){},stopPropagation(){}});
  assert.equal(y.timers.size,0);
});
