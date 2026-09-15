const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {runInNewContext} = require('node:vm');

function setup() {
  const handlers = {};
  const search = {value:'',addEventListener:(name,fn)=>handlers[name]=fn,focus(){this.focused=true;}};
  const status = {textContent:''};
  const options = ['Todas as categorias','Contas 30 dias','Contas 60 dias','Promoções'].map(name => {
    const link = {firstChild:{textContent:name},focus(){this.focused=true;}};
    return {hidden:false,querySelector:()=>link};
  });
  const pickerHandlers = {};
  const summary = {focus(){this.focused=true;}};
  const picker = {open:false,contains:()=>false,addEventListener:(name,fn)=>pickerHandlers[name]=fn,
    querySelector:selector=>selector==='[data-category-search]'?search:selector==='summary'?summary:status,
    querySelectorAll:()=>options};
  const Observer=class {observe(){}};
  runInNewContext(readFileSync(require.resolve('../assets/storev1-ui.js'),'utf8'),{MutationObserver:Observer,matchMedia:()=>({addEventListener(){}}),document:{body:{},
    getElementById:()=>null,addEventListener(){},
    querySelectorAll:selector=>selector==='[data-sv1-open]'||selector==='[data-sv1-carousel]'?[]:[picker]
  }});
  return {search,status,options,handlers,picker,pickerHandlers,summary};
}
test('filters immediately, ignores case and accents, and resets',()=>{
  const x=setup();
  x.search.value='PROMOCOES';x.handlers.input();
  assert.equal(x.options.filter(o=>!o.hidden).length,1);
  assert.equal(x.status.textContent,'1 categoria encontrada');
  x.search.value='30';x.handlers.input();assert.equal(x.options[1].hidden,false);
  x.search.value='zzz';x.handlers.input();assert.equal(x.status.textContent,'Nenhuma categoria encontrada.');
  assert.ok(x.options.every(o=>o.hidden));
  x.search.value='';x.handlers.input();assert.ok(x.options.every(o=>!o.hidden));
  assert.equal(x.status.textContent,'');
});
test('supports keyboard navigation and closing with Escape',()=>{
  const x=setup();x.picker.open=true;x.pickerHandlers.toggle();assert.equal(x.search.focused,true);
  x.search.value='60';x.handlers.input();x.handlers.keydown({key:'ArrowDown',preventDefault(){}});
  assert.equal(x.options[2].querySelector().focused,true);
  x.pickerHandlers.keydown({key:'Escape'});assert.equal(x.picker.open,false);assert.equal(x.summary.focused,true);
});
