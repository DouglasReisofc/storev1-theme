const {test}=require('node:test');
const assert=require('node:assert/strict');
const {readFileSync}=require('node:fs');
const {runInNewContext}=require('node:vm');

function setup(){
  const node=()=>({events:{},dataset:{},attributes:{},hidden:false,value:'',
    setAttribute(k,v){this.attributes[k]=v;},removeAttribute(k){delete this.attributes[k];},
    addEventListener(k,fn){this.events[k]=fn;},getBoundingClientRect(){return {top:0,bottom:800};},focus(){this.focused=true;},blur(){this.blurred=true;},scrollIntoView(){this.scrolled=true;},contains(){return false;}});
  const input=node(),results=node(),status=node(),close=node(),banner=node(),grid=node();
  grid.classList={toggle(k,v){grid.searchHidden=v;}};
  const toolbar={prepend(n){this.toggle=n;},querySelector(){return grid;}};
  const discovery={firstChild:banner,insertBefore(n,before){toolbar.panel=n;toolbar.before=before;}};
  const count={parentElement:toolbar,remove(){this.removed=true;}};
  const document={
    events:{},addEventListener(k,fn){this.events[k]=fn;},
    querySelector(s){return s==='.woocommerce-result-count'?count:s==='.sv1-discovery'?discovery:banner;},
    querySelectorAll(s){return s.startsWith('#main-content')?[grid]:[];},
    createElement(tag){const n=node();if(tag==='section')n.querySelector=s=>s==='input'?input:s==='[data-search-results]'?results:s==='[data-search-status]'?status:close;return n;}
  };
  const calls=[];
  const viewport={matches:true,addEventListener(name,fn){this.change=fn;}};
  runInNewContext(readFileSync(require.resolve('../assets/storev1-search.js'),'utf8'),{
    document,matchMedia:()=>viewport,window:{location:{origin:'https://shop.test'}},location:{href:'https://shop.test/'},URL,AbortController,
    fetch(url,opts){return new Promise(resolve=>calls.push({url,opts,resolve}));}
  });
  return {input,results,banner,grid,toolbar,count,close,calls,viewport,document};
}
test('opening search hides trigger and banner, places panel above discovery, and closing restores both',()=>{
  const x=setup();assert.equal(x.count.removed,true);assert.equal(x.toolbar.before,x.banner);
  x.toolbar.toggle.events.click();
  assert.equal(x.banner.hidden,true);assert.equal(x.grid.searchHidden,true);assert.equal(x.toolbar.panel.hidden,false);
  assert.equal(x.toolbar.toggle.hidden,true);assert.notEqual(x.toolbar.panel.scrolled,true);assert.notEqual(x.input.focused,true);
  assert.equal(x.calls[0].url.searchParams.get('q'),'');
  x.input.value='mega';x.input.events.input();assert.equal(x.banner.hidden,true);
  x.input.value='';x.input.events.input();assert.equal(x.banner.hidden,true);
  x.close.events.click();assert.equal(x.banner.hidden,false);assert.equal(x.grid.searchHidden,false);
  assert.equal(x.toolbar.toggle.hidden,false);
  assert.equal(x.toolbar.panel.hidden,true);assert.equal(x.calls.at(-1).opts.signal.aborted,true);
});
test('scroll gestures dismiss input focus without preventing product scrolling',()=>{
  const x=setup();x.toolbar.toggle.events.click();x.document.activeElement=x.input;
  x.document.events.touchstart({target:{closest(){return null;}},touches:[{clientY:200}]});
  x.document.events.touchmove({touches:[{clientY:195}]});
  assert.notEqual(x.input.blurred,true);
  x.document.events.touchmove({touches:[{clientY:160}]});assert.equal(x.input.blurred,true);
  x.input.blurred=false;x.document.events.wheel();assert.equal(x.input.blurred,true);
});
test('typing after scrolling returns the field into view without emptying the current results',()=>{
  const x=setup();x.toolbar.toggle.events.click();x.results.innerHTML='existing products';
  x.toolbar.panel.getBoundingClientRect=()=>({top:-600,bottom:800});
  x.input.value='mega';x.input.events.input();
  assert.equal(x.toolbar.panel.scrolled,true);assert.equal(x.results.innerHTML,'existing products');
});
test('desktop disables mobile canvas and breakpoint change restores the catalog',()=>{
  const x=setup();x.toolbar.toggle.events.click();
  x.viewport.matches=false;x.viewport.change({matches:false});
  assert.equal(x.toolbar.panel.hidden,true);assert.equal(x.banner.hidden,false);assert.equal(x.grid.searchHidden,false);
  x.toolbar.toggle.events.click();assert.equal(x.toolbar.panel.hidden,true);
});
