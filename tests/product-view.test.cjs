const {test}=require('node:test');
const assert=require('node:assert/strict');
const {readFileSync}=require('node:fs');
const {runInNewContext}=require('node:vm');
function setup(saved,blocked=false){
 const state={grid:false,saved};
 const buttons=['grid','cards'].map(view=>({dataset:{sv1View:view},setAttribute(k,v){this[k]=v;},addEventListener(k,fn){this[k]=fn;}}));
 const document={body:{classList:{toggle(k,v){state.grid=v;}}},getElementById:()=>null,addEventListener(){},querySelector:()=>buttons[0],querySelectorAll:s=>s==='[data-sv1-view]'?buttons:[]};
 runInNewContext(readFileSync(require.resolve('../assets/storev1-ui.js'),'utf8'),{document,localStorage:{getItem(){if(blocked)throw Error();return saved;},setItem(k,v){if(blocked)throw Error();state.saved=v;}}});
 return {state,buttons};
}
test('grid is default and both choices update the layout and saved preference',()=>{
 const {state,buttons}=setup(); assert.equal(state.grid,true); assert.equal(buttons[0]['aria-pressed'],'true');
 buttons[1].click(); assert.equal(state.grid,false); assert.equal(state.saved,'cards');
 buttons[0].click(); assert.equal(state.grid,true); assert.equal(state.saved,'grid');
});
test('saved cards are respected and invalid preferences fall back to grid',()=>{
 assert.equal(setup('cards').state.grid,false); assert.equal(setup('invalid').state.grid,true);
});
test('blocked storage does not break the switch',()=>{
 const {state,buttons}=setup(undefined,true); buttons[1].click(); assert.equal(state.grid,false);
});
