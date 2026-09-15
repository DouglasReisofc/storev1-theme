const {test}=require('node:test');
const assert=require('node:assert/strict');
const {readFileSync}=require('node:fs');
const {runInNewContext}=require('node:vm');

function setup(){
  const node=()=>({events:{},dataset:{},attributes:{},hidden:false,value:'',
    setAttribute(k,v){this.attributes[k]=v;},removeAttribute(k){delete this.attributes[k];},
    addEventListener(k,fn){this.events[k]=fn;},focus(){},scrollIntoView(){this.scrolled=true;},contains(){return false;}});
  const input=node(),results=node(),status=node(),close=node(),banner=node(),grid=node();
  grid.classList={toggle(k,v){grid.searchHidden=v;}};
  const toolbar={prepend(n){this.toggle=n;},querySelector(){return grid;}};
  const discovery={firstChild:banner,insertBefore(n,before){toolbar.panel=n;toolbar.before=before;}};
  const count={parentElement:toolbar,remove(){this.removed=true;}};
  const document={
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
  return {input,results,banner,grid,toolbar,count,close,calls,viewport};
}
test('opening search hides trigger and banner, places panel above discovery, and closing restores both',()=>{
  const x=setup();assert.equal(x.count.removed,true);assert.equal(x.toolbar.before,x.banner);
  x.toolbar.toggle.events.click();
  assert.equal(x.banner.hidden,true);assert.equal(x.grid.searchHidden,true);assert.equal(x.toolbar.panel.hidden,false);
  assert.equal(x.toolbar.toggle.hidden,true);assert.equal(x.toolbar.panel.scrolled,true);
  x.input.value='mega';x.input.events.input();assert.equal(x.banner.hidden,true);
  x.input.value='';x.input.events.input();assert.equal(x.banner.hidden,true);
  x.close.events.click();assert.equal(x.banner.hidden,false);assert.equal(x.grid.searchHidden,false);
  assert.equal(x.toolbar.toggle.hidden,false);
  assert.equal(x.toolbar.panel.hidden,true);assert.equal(x.calls.at(-1).opts.signal.aborted,true);
});
test('desktop disables mobile canvas and breakpoint change restores the catalog',()=>{
  const x=setup();x.toolbar.toggle.events.click();
  x.viewport.matches=false;x.viewport.change({matches:false});
  assert.equal(x.toolbar.panel.hidden,true);assert.equal(x.banner.hidden,false);assert.equal(x.grid.searchHidden,false);
  x.toolbar.toggle.events.click();assert.equal(x.toolbar.panel.hidden,true);
});
