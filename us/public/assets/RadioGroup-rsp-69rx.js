import{$t as e,C as t,Cn as n,Gn as r,K as i,L as a,Ln as o,Mt as s,Nt as c,Ot as l,Pt as u,R as d,V as f,Y as p,Yn as m,_ as h,ar as g,cn as _,en as v,f as y,fn as b,jt as x,kt as S,pn as C,rn as w,tn as T,v as E,w as D,wn as O,yr as k}from"./is-browser-dQ-mLBB2.js";import{t as A}from"./use-memo-Dodol12_.js";import{t as j}from"./use-merged-state-BNqaTJd4.js";import{d as M}from"./Button-BWtrbvO5.js";import{t as N}from"./flatten-LMGZxwim.js";import{t as P}from"./get-slot-6kXJmSMP.js";import{E as F}from"./index-Cc8HQXPT.js";var I=S(`radio`,`
 line-height: var(--n-label-line-height);
 outline: none;
 position: relative;
 user-select: none;
 -webkit-user-select: none;
 display: inline-flex;
 align-items: flex-start;
 flex-wrap: nowrap;
 font-size: var(--n-font-size);
 word-break: break-word;
`,[s(`checked`,[x(`dot`,`
 background-color: var(--n-color-active);
 `)]),x(`dot-wrapper`,`
 position: relative;
 flex-shrink: 0;
 flex-grow: 0;
 width: var(--n-radio-size);
 `),S(`radio-input`,`
 position: absolute;
 border: 0;
 width: 0;
 height: 0;
 opacity: 0;
 margin: 0;
 `),x(`dot`,`
 position: absolute;
 top: 50%;
 left: 0;
 transform: translateY(-50%);
 height: var(--n-radio-size);
 width: var(--n-radio-size);
 background: var(--n-color);
 box-shadow: var(--n-box-shadow);
 border-radius: 50%;
 transition:
 background-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 `,[l(`&::before`,`
 content: "";
 opacity: 0;
 position: absolute;
 left: 4px;
 top: 4px;
 height: calc(100% - 8px);
 width: calc(100% - 8px);
 border-radius: 50%;
 transform: scale(.8);
 background: var(--n-dot-color-active);
 transition: 
 opacity .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 transform .3s var(--n-bezier);
 `),s(`checked`,{boxShadow:`var(--n-box-shadow-active)`},[l(`&::before`,`
 opacity: 1;
 transform: scale(1);
 `)])]),x(`label`,`
 color: var(--n-text-color);
 padding: var(--n-label-padding);
 font-weight: var(--n-label-font-weight);
 display: inline-block;
 transition: color .3s var(--n-bezier);
 `),c(`disabled`,`
 cursor: pointer;
 `,[l(`&:hover`,[x(`dot`,{boxShadow:`var(--n-box-shadow-hover)`})]),s(`focus`,[l(`&:not(:active)`,[x(`dot`,{boxShadow:`var(--n-box-shadow-focus)`})])])]),s(`disabled`,`
 cursor: not-allowed;
 `,[x(`dot`,{boxShadow:`var(--n-box-shadow-disabled)`,backgroundColor:`var(--n-color-disabled)`},[l(`&::before`,{backgroundColor:`var(--n-dot-color-disabled)`}),s(`checked`,`
 opacity: 1;
 `)]),x(`label`,{color:`var(--n-text-color-disabled)`}),S(`radio-input`,`
 cursor: not-allowed;
 `)])]);b(),o();var L={name:String,value:{type:[String,Number,Boolean],default:`on`},checked:{type:Boolean,default:void 0},defaultChecked:Boolean,disabled:{type:Boolean,default:void 0},label:String,size:String,onUpdateChecked:[Function,Array],"onUpdate:checked":[Function,Array],checkedValue:{type:Boolean,default:void 0}},R=p(`n-radio-group`);function z(e){let t=C(R,null),{mergedClsPrefixRef:n,mergedComponentPropsRef:a}=i(e),o=M(e,{mergedSize(n){let{size:r}=e;if(r!==void 0)return r;if(t){let{mergedSizeRef:{value:e}}=t;if(e!==void 0)return e}return n?n.mergedSize.value:a?.value?.Radio?.size||`medium`},mergedDisabled(n){return!!(e.disabled||t?.disabledRef.value||n?.disabled.value)}}),{mergedSizeRef:s,mergedDisabledRef:c}=o,l=r(null),u=r(null),d=r(e.defaultChecked),f=m(e,`checked`),p=j(f,d),h=A(()=>t?t.valueRef.value===e.value:p.value),g=A(()=>{let{name:n}=e;if(n!==void 0)return n;if(t)return t.nameRef.value}),_=r(!1);function v(){if(t){let{doUpdateValue:n}=t,{value:r}=e;E(n,r)}else{let{onUpdateChecked:t,"onUpdate:checked":n}=e,{nTriggerFormInput:r,nTriggerFormChange:i}=o;t&&E(t,!0),n&&E(n,!0),r(),i(),d.value=!0}}function y(){c.value||h.value||v()}function b(){y(),l.value&&(l.value.checked=h.value)}function x(){_.value=!1}function S(){_.value=!0}return{mergedClsPrefix:t?t.mergedClsPrefixRef:n,inputRef:l,labelRef:u,mergedName:g,mergedDisabled:c,renderSafeChecked:h,focus:_,mergedSize:s,handleRadioInputChange:b,handleRadioInputBlur:x,handleRadioInputFocus:S}}b(),g();var B=[`value`,`name`,`checked`,`disabled`,`onChange`,`onFocus`,`onBlur`],V={...t.props,...L},H=_({name:`Radio`,props:V,setup(n){let r=z(n),a=t(`Radio`,`-radio`,I,F,n,r.mergedClsPrefix),o=e(()=>{let{mergedSize:{value:e}}=r,{common:{cubicBezierEaseInOut:t},self:{boxShadow:n,boxShadowActive:i,boxShadowDisabled:o,boxShadowFocus:s,boxShadowHover:c,color:l,colorDisabled:d,colorActive:f,textColor:p,textColorDisabled:m,dotColorActive:h,dotColorDisabled:g,labelPadding:_,labelLineHeight:v,labelFontWeight:y,[u(`fontSize`,e)]:b,[u(`radioSize`,e)]:x}}=a.value;return{"--n-bezier":t,"--n-label-line-height":v,"--n-label-font-weight":y,"--n-box-shadow":n,"--n-box-shadow-active":i,"--n-box-shadow-disabled":o,"--n-box-shadow-focus":s,"--n-box-shadow-hover":c,"--n-color":l,"--n-color-active":f,"--n-color-disabled":d,"--n-dot-color-active":h,"--n-dot-color-disabled":g,"--n-font-size":b,"--n-radio-size":x,"--n-text-color":p,"--n-text-color-disabled":m,"--n-label-padding":_}}),{inlineThemeDisabled:s,mergedClsPrefixRef:c,mergedRtlRef:l}=i(n),d=y(`Radio`,l,c),f=s?D(`radio`,e(()=>r.mergedSize.value[0]),o,n):void 0;return Object.assign(r,{rtlEnabled:d,cssVars:s?void 0:o,themeClass:f?.themeClass,onRender:f?.onRender})},render(){let{$slots:e,mergedClsPrefix:t,onRender:r,label:i}=this;return r?.(),(()=>{let r=a(`f8c6901d8cd45c02`);return n(),w(`label`,{class:d([`${t}-radio`,this.themeClass,this.rtlEnabled&&`${t}-radio--rtl`,this.mergedDisabled&&`${t}-radio--disabled`,this.renderSafeChecked&&`${t}-radio--checked`,this.focus&&`${t}-radio--focus`]),style:k(this.cssVars)},[v(`div`,{class:d(`${t}-radio__dot-wrapper`)},[r[0]||=f(`\xA0`,-1),v(`div`,{class:d([`${t}-radio__dot`,this.renderSafeChecked&&`${t}-radio__dot--checked`])},null,2),v(`input`,{ref:`inputRef`,type:`radio`,class:d(`${t}-radio-input`),value:this.value,name:this.mergedName,checked:this.renderSafeChecked,disabled:this.mergedDisabled,onChange:this.handleRadioInputChange,onFocus:this.handleRadioInputFocus,onBlur:this.handleRadioInputBlur},null,42,B)],2),f(()=>h(e.default,e=>!e&&!i?null:(n(),w(`div`,{ref:`labelRef`,class:d(`${t}-radio__label`)},[f(()=>e||i)],2))))],6)})()}}),U=S(`radio-group`,`
 display: inline-block;
 font-size: var(--n-font-size);
`,[x(`splitor`,`
 display: inline-block;
 vertical-align: bottom;
 width: 1px;
 transition:
 background-color .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 background: var(--n-button-border-color);
 `,[s(`checked`,{backgroundColor:`var(--n-button-border-color-active)`}),s(`disabled`,{opacity:`var(--n-opacity-disabled)`})]),s(`button-group`,`
 white-space: nowrap;
 height: var(--n-height);
 line-height: var(--n-height);
 `,[S(`radio-button`,{height:`var(--n-height)`,lineHeight:`var(--n-height)`}),x(`splitor`,{height:`var(--n-height)`})]),S(`radio-button`,`
 vertical-align: bottom;
 outline: none;
 position: relative;
 user-select: none;
 -webkit-user-select: none;
 display: inline-block;
 box-sizing: border-box;
 padding-left: 14px;
 padding-right: 14px;
 white-space: nowrap;
 transition:
 background-color .3s var(--n-bezier),
 opacity .3s var(--n-bezier),
 border-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 background: var(--n-button-color);
 color: var(--n-button-text-color);
 border-top: 1px solid var(--n-button-border-color);
 border-bottom: 1px solid var(--n-button-border-color);
 `,[S(`radio-input`,`
 pointer-events: none;
 position: absolute;
 border: 0;
 border-radius: inherit;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 opacity: 0;
 z-index: 1;
 `),x(`state-border`,`
 z-index: 1;
 pointer-events: none;
 position: absolute;
 box-shadow: var(--n-button-box-shadow);
 transition: box-shadow .3s var(--n-bezier);
 left: -1px;
 bottom: -1px;
 right: -1px;
 top: -1px;
 `),l(`&:first-child`,`
 border-top-left-radius: var(--n-button-border-radius);
 border-bottom-left-radius: var(--n-button-border-radius);
 border-left: 1px solid var(--n-button-border-color);
 `,[x(`state-border`,`
 border-top-left-radius: var(--n-button-border-radius);
 border-bottom-left-radius: var(--n-button-border-radius);
 `)]),l(`&:last-child`,`
 border-top-right-radius: var(--n-button-border-radius);
 border-bottom-right-radius: var(--n-button-border-radius);
 border-right: 1px solid var(--n-button-border-color);
 `,[x(`state-border`,`
 border-top-right-radius: var(--n-button-border-radius);
 border-bottom-right-radius: var(--n-button-border-radius);
 `)]),c(`disabled`,`
 cursor: pointer;
 `,[l(`&:hover`,[x(`state-border`,`
 transition: box-shadow .3s var(--n-bezier);
 box-shadow: var(--n-button-box-shadow-hover);
 `),c(`checked`,{color:`var(--n-button-text-color-hover)`})]),s(`focus`,[l(`&:not(:active)`,[x(`state-border`,{boxShadow:`var(--n-button-box-shadow-focus)`})])])]),s(`checked`,`
 background: var(--n-button-color-active);
 color: var(--n-button-text-color-active);
 border-color: var(--n-button-border-color-active);
 `),s(`disabled`,`
 cursor: not-allowed;
 opacity: var(--n-opacity-disabled);
 `)])]);b(),g(),o();var W=[`onFocusin`,`onFocusout`];function G(e,t,r){let i=[],a=!1;for(let o=0;o<e.length;++o){let s=e[o],c=s.type?.name;c===`RadioButton`&&(a=!0);let l=s.props;if(c!==`RadioButton`){i.push(s);continue}if(o===0)i.push(s);else{let e=i[i.length-1].props,a=t===e.value,o=e.disabled,c=t===l.value,u=l.disabled,f=(a?2:0)+ +!o,p=(c?2:0)+ +!u,m={[`${r}-radio-group__splitor--disabled`]:o,[`${r}-radio-group__splitor--checked`]:a},h={[`${r}-radio-group__splitor--disabled`]:u,[`${r}-radio-group__splitor--checked`]:c},g=f<p?h:m;i.push((n(),w(`div`,{key:1,class:d([`${r}-radio-group__splitor`,g])},null,2)),s)}}return{children:i,isButtonGroup:a}}var K={...t.props,name:String,options:Array,labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},value:[String,Number,Boolean],defaultValue:{type:[String,Number,Boolean],default:null},size:String,disabled:{type:Boolean,default:void 0},"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array]},q=_({name:`RadioGroup`,props:K,setup(n){let a=r(null),{mergedSizeRef:o,mergedDisabledRef:s,nTriggerFormChange:c,nTriggerFormInput:l,nTriggerFormBlur:d,nTriggerFormFocus:f}=M(n),{mergedClsPrefixRef:p,inlineThemeDisabled:h,mergedRtlRef:g}=i(n),_=t(`Radio`,`-radio-group`,U,F,n,p),v=r(n.defaultValue),b=m(n,`value`),x=j(b,v);function S(e){let{onUpdateValue:t,"onUpdate:value":r}=n;t&&E(t,e),r&&E(r,e),v.value=e,c(),l()}function C(e){let{value:t}=a;t&&(t.contains(e.relatedTarget)||f())}function w(e){let{value:t}=a;t&&(t.contains(e.relatedTarget)||d())}O(R,{mergedClsPrefixRef:p,nameRef:m(n,`name`),valueRef:x,disabledRef:s,mergedSizeRef:o,doUpdateValue:S});let T=y(`Radio`,g,p),k=e(()=>{let{value:e}=o,{common:{cubicBezierEaseInOut:t},self:{buttonBorderColor:n,buttonBorderColorActive:r,buttonBorderRadius:i,buttonBoxShadow:a,buttonBoxShadowFocus:s,buttonBoxShadowHover:c,buttonColor:l,buttonColorActive:d,buttonTextColor:f,buttonTextColorActive:p,buttonTextColorHover:m,opacityDisabled:h,[u(`buttonHeight`,e)]:g,[u(`fontSize`,e)]:v}}=_.value;return{"--n-font-size":v,"--n-bezier":t,"--n-button-border-color":n,"--n-button-border-color-active":r,"--n-button-border-radius":i,"--n-button-box-shadow":a,"--n-button-box-shadow-focus":s,"--n-button-box-shadow-hover":c,"--n-button-color":l,"--n-button-color-active":d,"--n-button-text-color":f,"--n-button-text-color-hover":m,"--n-button-text-color-active":p,"--n-height":g,"--n-opacity-disabled":h}}),A=h?D(`radio-group`,e(()=>o.value[0]),k,n):void 0;return{selfElRef:a,rtlEnabled:T,mergedClsPrefix:p,mergedValue:x,handleFocusout:w,handleFocusin:C,cssVars:h?void 0:k,themeClass:A?.themeClass,onRender:A?.onRender}},render(){let{mergedValue:e,mergedClsPrefix:t,handleFocusin:r,handleFocusout:i}=this,{options:a,labelField:o,valueField:s}=this.$props,{children:c,isButtonGroup:l}=G(a?a.map(e=>{let t=e[s];return n(),T(H,{key:typeof t==`boolean`?`__n_${t}`:t,value:t,disabled:e.disabled,label:e[o]},null,8,[`value`,`disabled`,`label`])}):N(P(this)),e,t);return this.onRender?.(),n(),w(`div`,{onFocusin:r,onFocusout:i,ref:`selfElRef`,class:d([`${t}-radio-group`,this.rtlEnabled&&`${t}-radio-group--rtl`,this.themeClass,l&&`${t}-radio-group--button-group`]),style:k(this.cssVars)},[f(()=>c)],46,W)}});export{z as i,H as n,L as r,q as t};