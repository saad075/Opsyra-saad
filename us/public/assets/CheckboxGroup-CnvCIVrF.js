import{$t as e,C as t,Cn as n,Ft as r,Gn as i,It as a,K as o,L as s,Ln as c,Mt as l,Ot as u,Pt as d,R as f,V as p,Y as m,Yn as h,Yt as g,_,ar as v,b as y,cn as b,en as x,f as S,fn as C,jt as w,kt as T,pn as E,rn as D,sn as O,tn as k,v as A,w as j,wn as M,yr as N}from"./is-browser-dQ-mLBB2.js";import{s as P}from"./client-BpOzf98Y.js";import{t as F}from"./use-memo-Dodol12_.js";import{t as I}from"./use-merged-state-BNqaTJd4.js";import{c as L,d as R,l as z}from"./Button-BWtrbvO5.js";import{L as B}from"./index-Cc8HQXPT.js";C();var V=()=>(()=>{let e=s(`75be776d8875fa17`);return e[0]||=x(`svg`,{viewBox:`0 0 64 64`,class:`check-icon`},[x(`path`,{d:`M50.42,16.76L22.34,39.45l-8.1-11.46c-1.12-1.58-3.3-1.96-4.88-0.84c-1.58,1.12-1.95,3.3-0.84,4.88l10.26,14.51  c0.56,0.79,1.42,1.31,2.38,1.45c0.16,0.02,0.32,0.03,0.48,0.03c0.8,0,1.57-0.27,2.2-0.78l30.99-25.03c1.5-1.21,1.74-3.42,0.52-4.92  C54.13,15.78,51.93,15.55,50.42,16.76z`})],-1)})();C();var H=()=>(()=>{let e=s(`c6eed899356c8404`);return e[0]||=x(`svg`,{viewBox:`0 0 100 100`,class:`line-icon`},[x(`path`,{d:`M80.2,55.5H21.4c-2.8,0-5.1-2.5-5.1-5.5l0,0c0-3,2.3-5.5,5.1-5.5h58.7c2.8,0,5.1,2.5,5.1,5.5l0,0C85.2,53.1,82.9,55.5,80.2,55.5z`})],-1)})(),U=u([T(`checkbox`,`
 font-size: var(--n-font-size);
 outline: none;
 cursor: pointer;
 display: inline-flex;
 flex-wrap: nowrap;
 align-items: flex-start;
 word-break: break-word;
 line-height: var(--n-size);
 --n-merged-color-table: var(--n-color-table);
 `,[l(`show-label`,`line-height: var(--n-label-line-height);`),u(`&:hover`,[T(`checkbox-box`,[w(`border`,`border: var(--n-border-checked);`)])]),u(`&:focus:not(:active)`,[T(`checkbox-box`,[w(`border`,`
 border: var(--n-border-focus);
 box-shadow: var(--n-box-shadow-focus);
 `)])]),l(`inside-table`,[T(`checkbox-box`,`
 background-color: var(--n-merged-color-table);
 `)]),l(`checked`,[T(`checkbox-box`,`
 background-color: var(--n-color-checked);
 `,[T(`checkbox-icon`,[u(`.check-icon`,`
 opacity: 1;
 transform: scale(1);
 `)])])]),l(`indeterminate`,[T(`checkbox-box`,[T(`checkbox-icon`,[u(`.check-icon`,`
 opacity: 0;
 transform: scale(.5);
 `),u(`.line-icon`,`
 opacity: 1;
 transform: scale(1);
 `)])])]),l(`checked, indeterminate`,[u(`&:focus:not(:active)`,[T(`checkbox-box`,[w(`border`,`
 border: var(--n-border-checked);
 box-shadow: var(--n-box-shadow-focus);
 `)])]),T(`checkbox-box`,`
 background-color: var(--n-color-checked);
 border-left: 0;
 border-top: 0;
 `,[w(`border`,{border:`var(--n-border-checked)`})])]),l(`disabled`,{cursor:`not-allowed`},[l(`checked`,[T(`checkbox-box`,`
 background-color: var(--n-color-disabled-checked);
 `,[w(`border`,{border:`var(--n-border-disabled-checked)`}),T(`checkbox-icon`,[u(`.check-icon, .line-icon`,{fill:`var(--n-check-mark-color-disabled-checked)`})])])]),T(`checkbox-box`,`
 background-color: var(--n-color-disabled);
 `,[w(`border`,`
 border: var(--n-border-disabled);
 `),T(`checkbox-icon`,[u(`.check-icon, .line-icon`,`
 fill: var(--n-check-mark-color-disabled);
 `)])]),w(`label`,`
 color: var(--n-text-color-disabled);
 `)]),T(`checkbox-box-wrapper`,`
 position: relative;
 width: var(--n-size);
 flex-shrink: 0;
 flex-grow: 0;
 user-select: none;
 -webkit-user-select: none;
 `),T(`checkbox-box`,`
 position: absolute;
 left: 0;
 top: 50%;
 transform: translateY(-50%);
 height: var(--n-size);
 width: var(--n-size);
 display: inline-block;
 box-sizing: border-box;
 border-radius: var(--n-border-radius);
 background-color: var(--n-color);
 transition: background-color 0.3s var(--n-bezier);
 `,[w(`border`,`
 transition:
 border-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 border-radius: inherit;
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 border: var(--n-border);
 `),T(`checkbox-icon`,`
 display: flex;
 align-items: center;
 justify-content: center;
 position: absolute;
 left: 1px;
 right: 1px;
 top: 1px;
 bottom: 1px;
 `,[u(`.check-icon, .line-icon`,`
 width: 100%;
 fill: var(--n-check-mark-color);
 opacity: 0;
 transform: scale(0.5);
 transform-origin: center;
 transition:
 fill 0.3s var(--n-bezier),
 transform 0.3s var(--n-bezier),
 opacity 0.3s var(--n-bezier),
 border-color 0.3s var(--n-bezier);
 `),L({left:`1px`,top:`1px`})])]),w(`label`,`
 color: var(--n-text-color);
 transition: color .3s var(--n-bezier);
 user-select: none;
 -webkit-user-select: none;
 padding: var(--n-label-padding);
 font-weight: var(--n-label-font-weight);
 `,[u(`&:empty`,{display:`none`})])]),r(T(`checkbox`,`
 --n-merged-color-table: var(--n-color-table-modal);
 `)),a(T(`checkbox`,`
 --n-merged-color-table: var(--n-color-table-popover);
 `))]);C(),v(),c();var W=[`id`],G=[`tabindex`,`aria-checked`,`aria-labelledby`,`onKeyup`,`onKeydown`,`onClick`],K={...t.props,size:String,checked:{type:[Boolean,String,Number],default:void 0},defaultChecked:{type:[Boolean,String,Number],default:!1},value:[String,Number],disabled:{type:Boolean,default:void 0},indeterminate:Boolean,label:String,focusable:{type:Boolean,default:!0},checkedValue:{type:[Boolean,String,Number],default:!0},uncheckedValue:{type:[Boolean,String,Number],default:!1},"onUpdate:checked":[Function,Array],onUpdateChecked:[Function,Array],privateInsideTable:Boolean,onChange:[Function,Array]},q=b({name:`Checkbox`,props:K,setup(n){let r=E(J,null),a=i(null),{mergedClsPrefixRef:s,inlineThemeDisabled:c,mergedRtlRef:l,mergedComponentPropsRef:u}=o(n),f=i(n.defaultChecked),p=h(n,`checked`),m=I(p,f),g=F(()=>{if(r){let e=r.valueSetRef.value;return e&&n.value!==void 0?e.has(n.value):!1}return m.value===n.checkedValue}),_=R(n,{mergedSize(e){let{size:t}=n;if(t!==void 0)return t;if(r){let{value:e}=r.mergedSizeRef;if(e!==void 0)return e}if(e){let{mergedSize:t}=e;if(t!==void 0)return t.value}return u?.value?.Checkbox?.size||`medium`},mergedDisabled(e){let{disabled:t}=n;if(t!==void 0)return t;if(r){if(r.disabledRef.value)return!0;let{maxRef:{value:e},checkedCountRef:t}=r;if(e!==void 0&&t.value>=e&&!g.value)return!0;let{minRef:{value:n}}=r;if(n!==void 0&&t.value<=n&&g.value)return!0}return e?e.disabled.value:!1}}),{mergedDisabledRef:v,mergedSizeRef:y}=_,b=t(`Checkbox`,`-checkbox`,U,B,n,s);function x(e){if(r&&n.value!==void 0)r.toggleCheckbox(!g.value,n.value);else{let{onChange:t,"onUpdate:checked":r,onUpdateChecked:i}=n,{nTriggerFormInput:a,nTriggerFormChange:o}=_,s=g.value?n.uncheckedValue:n.checkedValue;r&&A(r,s,e),i&&A(i,s,e),t&&A(t,s,e),a(),o(),f.value=s}}function C(e){v.value||x(e)}function w(e){if(!v.value)switch(e.key){case` `:case`Enter`:x(e)}}function T(e){e.key===` `&&e.preventDefault()}let D={focus:()=>{a.value?.focus()},blur:()=>{a.value?.blur()}},O=S(`Checkbox`,l,s),k=e(()=>{let{value:e}=y,{common:{cubicBezierEaseInOut:t},self:{borderRadius:n,color:r,colorChecked:i,colorDisabled:a,colorTableHeader:o,colorTableHeaderModal:s,colorTableHeaderPopover:c,checkMarkColor:l,checkMarkColorDisabled:u,border:f,borderFocus:p,borderDisabled:m,borderChecked:h,boxShadowFocus:g,textColor:_,textColorDisabled:v,checkMarkColorDisabledChecked:x,colorDisabledChecked:S,borderDisabledChecked:C,labelPadding:w,labelLineHeight:T,labelFontWeight:E,[d(`fontSize`,e)]:D,[d(`size`,e)]:O}}=b.value;return{"--n-label-line-height":T,"--n-label-font-weight":E,"--n-size":O,"--n-bezier":t,"--n-border-radius":n,"--n-border":f,"--n-border-checked":h,"--n-border-focus":p,"--n-border-disabled":m,"--n-border-disabled-checked":C,"--n-box-shadow-focus":g,"--n-color":r,"--n-color-checked":i,"--n-color-table":o,"--n-color-table-modal":s,"--n-color-table-popover":c,"--n-color-disabled":a,"--n-color-disabled-checked":S,"--n-text-color":_,"--n-text-color-disabled":v,"--n-check-mark-color":l,"--n-check-mark-color-disabled":u,"--n-check-mark-color-disabled-checked":x,"--n-font-size":D,"--n-label-padding":w}}),M=c?j(`checkbox`,e(()=>y.value[0]),k,n):void 0;return Object.assign(_,D,{rtlEnabled:O,selfRef:a,mergedClsPrefix:s,mergedDisabled:v,renderedChecked:g,mergedTheme:b,labelId:P(),handleClick:C,handleKeyUp:w,handleKeyDown:T,cssVars:c?void 0:k,themeClass:M?.themeClass,onRender:M?.onRender})},render(){let{$slots:e,renderedChecked:t,mergedDisabled:r,indeterminate:i,privateInsideTable:a,cssVars:o,labelId:c,label:l,mergedClsPrefix:u,focusable:d,handleKeyUp:m,handleKeyDown:h,handleClick:g}=this;this.onRender?.();let v=_(e.default,e=>l||e?(n(),D(`span`,{key:1,class:f(`${u}-checkbox__label`),id:c},[p(()=>l||e)],10,W)):null);return(()=>{let e=s(`70be6e74cd27cb50`);return n(),D(`div`,{ref:`selfRef`,class:f([`${u}-checkbox`,this.themeClass,this.rtlEnabled&&`${u}-checkbox--rtl`,t&&`${u}-checkbox--checked`,r&&`${u}-checkbox--disabled`,i&&`${u}-checkbox--indeterminate`,a&&`${u}-checkbox--inside-table`,v&&`${u}-checkbox--show-label`]),tabindex:r||!d?void 0:0,role:`checkbox`,"aria-checked":i?`mixed`:t,"aria-labelledby":c,style:N(o),onKeyup:m,onKeydown:h,onClick:g,onMousedown:e[0]||=()=>{y(`selectstart`,window,e=>{e.preventDefault()},{once:!0})}},[x(`div`,{class:f(`${u}-checkbox-box-wrapper`)},[e[1]||=p(`\xA0`,-1),x(`div`,{class:f(`${u}-checkbox-box`)},[O(z,null,{default:()=>this.indeterminate?(n(),D(`div`,{key:`indeterminate`,class:f(`${u}-checkbox-icon`)},[p(()=>H())],2)):(n(),D(`div`,{key:`check`,class:f(`${u}-checkbox-icon`)},[p(()=>V())],2))},1024),x(`div`,{class:f(`${u}-checkbox-box__border`)},null,2)],2)],2),p(()=>v)],46,G)})()}});C(),c();var J=m(`n-checkbox-group`),Y=b({name:`CheckboxGroup`,props:{min:Number,max:Number,size:String,options:Array,labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},value:Array,defaultValue:{type:Array,default:null},disabled:{type:Boolean,default:void 0},"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array],onChange:[Function,Array]},setup(t){let{mergedClsPrefixRef:n}=o(t),r=R(t),{mergedSizeRef:a,mergedDisabledRef:s}=r,c=i(t.defaultValue),l=e(()=>t.value),u=I(l,c),d=e(()=>u.value?.length||0),f=e(()=>Array.isArray(u.value)?new Set(u.value):new Set);function p(e,n){let{nTriggerFormInput:i,nTriggerFormChange:a}=r,{onChange:o,"onUpdate:value":s,onUpdateValue:l}=t;if(Array.isArray(u.value)){let t=Array.from(u.value),r=t.findIndex(e=>e===n);e?~r||(t.push(n),l&&A(l,t,{actionType:`check`,value:n}),s&&A(s,t,{actionType:`check`,value:n}),i(),a(),c.value=t,o&&A(o,t)):~r&&(t.splice(r,1),l&&A(l,t,{actionType:`uncheck`,value:n}),s&&A(s,t,{actionType:`uncheck`,value:n}),o&&A(o,t),c.value=t,i(),a())}else e?(l&&A(l,[n],{actionType:`check`,value:n}),s&&A(s,[n],{actionType:`check`,value:n}),o&&A(o,[n]),c.value=[n],i(),a()):(l&&A(l,[],{actionType:`uncheck`,value:n}),s&&A(s,[],{actionType:`uncheck`,value:n}),o&&A(o,[]),c.value=[],i(),a())}return M(J,{checkedCountRef:d,maxRef:h(t,`max`),minRef:h(t,`min`),valueSetRef:f,disabledRef:s,mergedSizeRef:a,toggleCheckbox:p}),{mergedClsPrefix:n}},render(){let{options:e,labelField:t,valueField:r}=this.$props;return n(),D(`div`,{class:f(`${this.mergedClsPrefix}-checkbox-group`),role:`group`},[e?(n(),D(g,{key:0},[p(()=>e.map(e=>{let i=e[r];return n(),k(q,{key:i,value:i,disabled:e.disabled,label:e[t]},null,8,[`value`,`disabled`,`label`])}))],64)):(n(),D(g,{key:1},[p(()=>this.$slots.default?.())],64))],2)}});export{q as n,Y as t};