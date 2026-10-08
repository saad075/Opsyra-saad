import{$t as e,C as t,Cn as n,F as r,Gn as i,Gt as a,Ht as o,I as s,K as c,L as l,Ln as u,M as d,Mn as f,Mt as p,Nt as m,Ot as h,P as g,Pn as _,Pt as v,R as y,U as b,V as x,Yn as S,Yt as C,_ as w,_n as T,a as E,ar as D,bn as O,c as k,cn as A,en as j,f as ee,fn as M,gn as N,h as P,hn as F,jn as I,jt as L,kt as R,pn as z,rn as B,s as V,sn as H,tn as U,u as te,un as W,v as G,w as ne,wn as K,x as re,xn as q,yn as J,yr as Y,z as X,zt as Z}from"./is-browser-dQ-mLBB2.js";import{t as ie}from"./use-locale-CLOZNvmi.js";import{c as ae,d as Q,f as oe,l as se,n as ce,o as le,r as ue,s as de,t as fe,u as pe}from"./create-0yRyaERx.js";import{t as me}from"./next-frame-once-qdYFoq8G.js";import{t as he}from"./Empty-BHiJI_Ki.js";import{t as $}from"./use-memo-Dodol12_.js";import{n as ge,r as _e,t as ve}from"./cssr-DWJ_V8AC.js";import{t as ye}from"./use-merged-state-BNqaTJd4.js";import{d as be,f as xe,s as Se}from"./Button-BWtrbvO5.js";import{t as Ce}from"./src-CYAk055Z.js";import{t as we}from"./Tag-DSX2T2O4.js";import{t as Te}from"./Suffix-qC4GDYU9.js";import{$ as Ee,F as De,H as Oe,I as ke,V as Ae,ct as je,j as Me}from"./index-Cc8HQXPT.js";function Ne(e){return e&-e}var Pe=class{constructor(e,t){this.l=e,this.min=t;let n=Array(e+1);for(let t=0;t<e+1;++t)n[t]=0;this.ft=n}add(e,t){if(t===0)return;let{l:n,ft:r}=this;for(e+=1;e<=n;)r[e]+=t,e+=Ne(e)}get(e){return this.sum(e+1)-this.sum(e)}sum(e){if(e===void 0&&(e=this.l),e<=0)return 0;let{ft:t,min:n,l:r}=this;if(e>r)throw Error("[FinweckTree.sum]: `i` is larger than length.");let i=e*n;for(;e>0;)i+=t[e],e-=Ne(e);return i}getBound(e){let t=0,n=this.l;for(;n>t;){let r=Math.floor((t+n)/2),i=this.sum(r);if(i>e){n=r;continue}if(i<e){if(t===r)return this.sum(t+1)<=e?t+1:r;t=r}else return r}return t}},Fe;function Ie(){return typeof document>`u`?!1:(Fe===void 0&&(Fe=`matchMedia`in window&&window.matchMedia(`(pointer:coarse)`).matches),Fe)}var Le;function Re(){return typeof document>`u`?1:(Le===void 0&&(Le=`chrome`in window?window.devicePixelRatio:1),Le)}var ze=`VVirtualListXScroll`;M(),u();function Be({columnsRef:t,renderColRef:n,renderItemWithColsRef:r}){let a=i(0),o=i(0),s=e(()=>{let e=t.value;if(e.length===0)return null;let n=new Pe(e.length,0);return e.forEach((e,t)=>{n.add(t,e.width)}),n}),c=$(()=>{let e=s.value;return e===null?0:Math.max(e.getBound(o.value)-1,0)}),l=e=>{let t=s.value;return t===null?0:t.sum(e)},u=$(()=>{let e=s.value;return e===null?0:Math.min(e.getBound(o.value+a.value)+1,t.value.length-1)});return K(ze,{startIndexRef:c,endIndexRef:u,columnsRef:t,renderColRef:n,renderItemWithColsRef:r,getLeft:l}),{listWidthRef:a,scrollLeftRef:o}}M();var Ve=A({name:`VirtualListRow`,props:{index:{type:Number,required:!0},item:{type:Object,required:!0}},setup(){let{startIndexRef:e,endIndexRef:t,columnsRef:n,getLeft:r,renderColRef:i,renderItemWithColsRef:a}=z(ze);return{startIndex:e,endIndex:t,columns:n,renderCol:i,renderItemWithCols:a,getLeft:r}},render(){let{startIndex:e,endIndex:t,columns:n,renderCol:r,renderItemWithCols:i,getLeft:a,item:o}=this;if(i!=null)return i({itemIndex:this.index,startColIndex:e,endColIndex:t,allColumns:n,item:o,getLeft:a});if(r!=null){let i=[];for(let s=e;s<=t;++s){let e=n[s];i.push(r({column:e,left:a(s),item:o}))}return i}return null}});M(),u();var He=ve(`.v-vl`,{maxHeight:`inherit`,height:`100%`,overflow:`auto`,minWidth:`1px`},[ve(`&:not(.v-vl--show-scrollbar)`,{scrollbarWidth:`none`},[ve(`&::-webkit-scrollbar, &::-webkit-scrollbar-track-piece, &::-webkit-scrollbar-thumb`,{width:0,height:0,display:`none`})])]),Ue=A({name:`VirtualList`,inheritAttrs:!1,props:{showScrollbar:{type:Boolean,default:!0},columns:{type:Array,default:()=>[]},renderCol:Function,renderItemWithCols:Function,items:{type:Array,default:()=>[]},itemSize:{type:Number,required:!0},itemResizable:Boolean,itemsStyle:[String,Object],visibleItemsTag:{type:[String,Object],default:`div`},visibleItemsProps:Object,ignoreItemResize:Boolean,onScroll:Function,onWheel:Function,onResize:Function,defaultScrollKey:[Number,String],defaultScrollIndex:Number,keyField:{type:String,default:`key`},paddingTop:{type:[Number,String],default:0},paddingBottom:{type:[Number,String],default:0}},setup(t){let n=b();He.mount({id:`vueuc/virtual-list`,head:!0,anchorMetaName:ge,ssr:n}),q(()=>{let{defaultScrollIndex:e,defaultScrollKey:n}=t;e==null?n!=null&&x({key:n}):x({index:e})});let a=!1,o=!1;T(()=>{if(a=!1,!o){o=!0;return}x({top:_.value,left:l.value})}),O(()=>{a=!0,o||=!0});let s=$(()=>{if(t.renderCol==null&&t.renderItemWithCols==null||t.columns.length===0)return;let e=0;return t.columns.forEach(t=>{e+=t.width}),e}),c=e(()=>{let e=new Map,{keyField:n}=t;return t.items.forEach((t,r)=>{e.set(t[n],r)}),e}),{scrollLeftRef:l,listWidthRef:u}=Be({columnsRef:S(t,`columns`),renderColRef:S(t,`renderCol`),renderItemWithColsRef:S(t,`renderItemWithCols`)}),f=i(null),p=i(void 0),m=new Map,h=e(()=>{let{items:e,itemSize:n,keyField:r}=t,i=new Pe(e.length,n);return e.forEach((e,t)=>{let n=e[r],a=m.get(n);a!==void 0&&i.add(t,a)}),i}),g=i(0),_=i(0),v=$(()=>Math.max(h.value.getBound(_.value-d(t.paddingTop))-1,0)),y=e(()=>{let{value:e}=p;if(e===void 0)return[];let{items:n,itemSize:r}=t,i=v.value,a=Math.min(i+Math.ceil(e/r+1),n.length-1),o=[];for(let e=i;e<=a;++e)o.push(n[e]);return o}),x=(e,t)=>{if(typeof e==`number`){D(e,t,`auto`);return}let{left:n,top:r,index:i,key:a,position:o,behavior:s,debounce:l=!0}=e;if(n!==void 0||r!==void 0)D(n,r,s);else if(i!==void 0)E(i,s,l);else if(a!==void 0){let e=c.value.get(a);e!==void 0&&E(e,s,l)}else o===`bottom`?D(0,2**53-1,s):o===`top`&&D(0,0,s)},C,w=null;function E(e,n,r){let i=f.value;if(i==null)return;let{value:a}=h,o=a.sum(e)+d(t.paddingTop);if(!r)i.scrollTo({left:0,top:o,behavior:n});else{C=e,w!==null&&window.clearTimeout(w),w=window.setTimeout(()=>{C=void 0,w=null},16);let{scrollTop:t,offsetHeight:r}=i;if(o>t){let s=a.get(e);o+s<=t+r||i.scrollTo({left:0,top:o+s-r,behavior:n})}else i.scrollTo({left:0,top:o,behavior:n})}}function D(e,t,n){f.value?.scrollTo({left:e,top:t,behavior:n})}function k(e,n){if(a||t.ignoreItemResize||F(n.target))return;let{value:r}=h,i=c.value.get(e),o=r.get(i),s=n.borderBoxSize?.[0]?.blockSize??n.contentRect.height;if(s===o)return;s-t.itemSize===0?m.delete(e):m.set(e,s-t.itemSize);let l=s-o;if(l===0)return;r.add(i,l);let u=f.value;if(u!=null){if(C===void 0){let e=r.sum(i);u.scrollTop>e&&u.scrollBy(0,l)}else(i<C||i===C&&s+r.sum(i)>u.scrollTop+u.offsetHeight)&&u.scrollBy(0,l);P()}g.value++}let A=!Ie(),j=!1;function ee(e){var n;(n=t.onScroll)==null||n.call(t,e),(!A||!j)&&P()}function M(e){var n;if((n=t.onWheel)==null||n.call(t,e),A){let t=f.value;if(t!=null){if(e.deltaX===0&&(t.scrollTop===0&&e.deltaY<=0||t.scrollTop+t.offsetHeight>=t.scrollHeight&&e.deltaY>=0))return;e.preventDefault(),t.scrollTop+=e.deltaY/Re(),t.scrollLeft+=e.deltaX/Re(),P(),j=!0,me(()=>{j=!1})}}}function N(e){if(a||F(e.target))return;if(t.renderCol==null&&t.renderItemWithCols==null){if(e.contentRect.height===p.value)return}else if(e.contentRect.height===p.value&&e.contentRect.width===u.value)return;p.value=e.contentRect.height,u.value=e.contentRect.width;let{onResize:n}=t;n!==void 0&&n(e)}function P(){let{value:e}=f;e!=null&&(_.value=e.scrollTop,l.value=e.scrollLeft)}function F(e){let t=e;for(;t!==null;){if(t.style.display===`none`)return!0;t=t.parentElement}return!1}return{listHeight:p,listStyle:{overflow:`auto`},keyToIndex:c,itemsStyle:e(()=>{let{itemResizable:e}=t,n=r(h.value.sum());return g.value,[t.itemsStyle,{boxSizing:`content-box`,width:r(s.value),height:e?``:n,minHeight:e?n:``,paddingTop:r(t.paddingTop),paddingBottom:r(t.paddingBottom)}]}),visibleItemsStyle:e(()=>(g.value,{transform:`translateY(${r(h.value.sum(v.value))})`})),viewportItems:y,listElRef:f,itemsElRef:i(null),scrollTo:x,handleListResize:N,handleListScroll:ee,handleListWheel:M,handleItemResize:k}},render(){let{itemResizable:e,keyField:t,keyToIndex:n,visibleItemsTag:r}=this;return W(V,{onResize:this.handleListResize},{default:()=>{var i;return W(`div`,F(this.$attrs,{class:[`v-vl`,this.showScrollbar&&`v-vl--show-scrollbar`],onScroll:this.handleListScroll,onWheel:this.handleListWheel,ref:`listElRef`}),[this.items.length===0?(i=this.$slots).empty?.call(i):W(`div`,{ref:`itemsElRef`,class:`v-vl-items`,style:this.itemsStyle},[W(r,Object.assign({class:`v-vl-visible-items`,style:this.visibleItemsStyle},this.visibleItemsProps),{default:()=>{let{renderCol:r,renderItemWithCols:i}=this;return this.viewportItems.map(a=>{let o=a[t],s=n.get(o),c=r==null?void 0:W(Ve,{index:s,item:a}),l=i==null?void 0:W(Ve,{index:s,item:a}),u=this.$slots.default({item:a,renderedCols:c,renderedItemWithCols:l,index:s})[0];return e?W(V,{key:o,onResize:e=>this.handleItemResize(o,e)},{default:()=>u}):(u.key=o,u)})}})])])}})}});function We(e){switch(typeof e){case`string`:return e||void 0;case`number`:return String(e);default:return}}M();function Ge(e,t){t&&(q(()=>{let{value:n}=e;n&&k.registerHandler(n,t)}),I(e,(e,t)=>{t&&k.unregisterHandler(t)},{deep:!1}),J(()=>{let{value:t}=e;t&&k.unregisterHandler(t)}))}M();var Ke=A({props:{onFocus:Function,onBlur:Function},setup(e){return()=>(()=>{let t=l(`d16ead82505dc285`);return n(),B(`div`,{style:`width: 0; height: 0`,tabindex:0,onFocus:t[0]||=t=>e.onFocus?.(t),onBlur:t[1]||=t=>e.onBlur?.(t)},null,32)})()}});M();var qe=A({name:`NBaseSelectGroupHeader`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(){let{renderLabelRef:e,renderOptionRef:t,labelFieldRef:n,nodePropsRef:r}=z(Q);return{labelField:n,nodeProps:r,renderLabel:e,renderOption:t}},render(){let{clsPrefix:e,renderLabel:t,renderOption:r,nodeProps:i,tmNode:{rawNode:a}}=this,o=i?.(a),s=t?t(a,!1):Oe(a[this.labelField],a,!1),c=(n(),B(`div`,F(o,{class:[`${e}-base-select-group-header`,o?.class]}),[x(()=>s)],16));return a.render?a.render({node:c,option:a}):r?r({node:c,option:a,selected:!1}):c}});function Je(e){let t=e.filter(e=>e!==void 0);if(t.length!==0)return t.length===1?t[0]:t=>{e.forEach(e=>{e&&e(t)})}}M();var Ye=A({name:`Checkmark`,render(){return(()=>{let e=l(`3c84eac8ae4e1f96`);return e[0]||=j(`svg`,{xmlns:`http://www.w3.org/2000/svg`,viewBox:`0 0 16 16`},[j(`g`,{fill:`none`},[j(`path`,{d:`M14.046 3.486a.75.75 0 0 1-.032 1.06l-7.93 7.474a.85.85 0 0 1-1.188-.022l-2.68-2.72a.75.75 0 1 1 1.068-1.053l2.234 2.267l7.468-7.038a.75.75 0 0 1 1.06.032z`,fill:`currentColor`})])],-1)})()}});o(),M();var Xe=[`onClick`,`onMouseenter`,`onMousemove`];function Ze(e,t){return n(),U(Z,{name:`fade-in-scale-up-transition`},{default:()=>e?(n(),U(re,{key:1,clsPrefix:t,class:y(`${t}-base-select-option__check`)},{default:()=>W(Ye)},1032,[`clsPrefix`,`class`])):null},1024)}var Qe=A({name:`NBaseSelectOption`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(e){let{valueRef:t,pendingTmNodeRef:n,multipleRef:r,valueSetRef:i,renderLabelRef:a,renderOptionRef:o,labelFieldRef:s,valueFieldRef:c,showCheckmarkRef:l,nodePropsRef:u,handleOptionClick:d,handleOptionMouseEnter:f}=z(Q),p=$(()=>{let{value:t}=n;return t?e.tmNode.key===t.key:!1});function m(t){let{tmNode:n}=e;n.disabled||d(t,n)}function h(t){let{tmNode:n}=e;n.disabled||f(t,n)}function g(t){let{tmNode:n}=e,{value:r}=p;n.disabled||r||f(t,n)}return{multiple:r,isGrouped:$(()=>{let{tmNode:t}=e,{parent:n}=t;return n&&n.rawNode.type===`group`}),showCheckmark:l,nodeProps:u,isPending:p,isSelected:$(()=>{let{value:n}=t,{value:a}=r;if(n===null)return!1;let o=e.tmNode.rawNode[c.value];if(a){let{value:e}=i;return e.has(o)}return n===o}),labelField:s,renderLabel:a,renderOption:o,handleMouseMove:g,handleMouseEnter:h,handleClick:m}},render(){let{clsPrefix:e,tmNode:{rawNode:t},isSelected:r,isPending:i,isGrouped:a,showCheckmark:o,nodeProps:s,renderOption:c,renderLabel:l,handleClick:u,handleMouseEnter:d,handleMouseMove:f}=this,p=Ze(r,e),m=l?[l(t,r),o&&p]:[Oe(t[this.labelField],t,r),o&&p],h=s?.(t),g=(n(),B(`div`,F(h,{class:[`${e}-base-select-option`,t.class,h?.class,{[`${e}-base-select-option--disabled`]:t.disabled,[`${e}-base-select-option--selected`]:r,[`${e}-base-select-option--grouped`]:a,[`${e}-base-select-option--pending`]:i,[`${e}-base-select-option--show-checkmark`]:o}],style:[h?.style||``,t.style||``],onClick:Je([u,h?.onClick]),onMouseenter:Je([d,h?.onMouseenter]),onMousemove:Je([f,h?.onMousemove])}),[j(`div`,{class:y(`${e}-base-select-option__content`)},[x(()=>m)],2)],16,Xe));return t.render?t.render({node:g,option:t,selected:r}):c?c({node:g,option:t,selected:r}):g}}),$e=R(`base-select-menu`,`
 line-height: 1.5;
 outline: none;
 z-index: 0;
 position: relative;
 border-radius: var(--n-border-radius);
 transition:
 background-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 background-color: var(--n-color);
`,[R(`scrollbar`,`
 max-height: var(--n-height);
 `),R(`virtual-list`,`
 max-height: var(--n-height);
 `),R(`base-select-option`,`
 min-height: var(--n-option-height);
 font-size: var(--n-option-font-size);
 display: flex;
 align-items: center;
 `,[L(`content`,`
 z-index: 1;
 white-space: nowrap;
 text-overflow: ellipsis;
 overflow: hidden;
 `)]),R(`base-select-group-header`,`
 min-height: var(--n-option-height);
 font-size: .93em;
 display: flex;
 align-items: center;
 `),R(`base-select-menu-option-wrapper`,`
 position: relative;
 width: 100%;
 `),L(`loading, empty`,`
 display: flex;
 padding: 12px 32px;
 flex: 1;
 justify-content: center;
 `),L(`loading`,`
 color: var(--n-loading-color);
 font-size: var(--n-loading-size);
 `),L(`header`,`
 padding: 8px var(--n-option-padding-left);
 font-size: var(--n-option-font-size);
 transition: 
 color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 border-bottom: 1px solid var(--n-action-divider-color);
 color: var(--n-action-text-color);
 `),L(`action`,`
 padding: 8px var(--n-option-padding-left);
 font-size: var(--n-option-font-size);
 transition: 
 color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 border-top: 1px solid var(--n-action-divider-color);
 color: var(--n-action-text-color);
 `),R(`base-select-group-header`,`
 position: relative;
 cursor: default;
 padding: var(--n-option-padding);
 color: var(--n-group-header-text-color);
 `),R(`base-select-option`,`
 cursor: pointer;
 position: relative;
 padding: var(--n-option-padding);
 transition:
 color .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 box-sizing: border-box;
 color: var(--n-option-text-color);
 opacity: 1;
 `,[p(`show-checkmark`,`
 padding-right: calc(var(--n-option-padding-right) + 20px);
 `),h(`&::before`,`
 content: "";
 position: absolute;
 left: 4px;
 right: 4px;
 top: 0;
 bottom: 0;
 border-radius: var(--n-border-radius);
 transition: background-color .3s var(--n-bezier);
 `),h(`&:active`,`
 color: var(--n-option-text-color-pressed);
 `),p(`grouped`,`
 padding-left: calc(var(--n-option-padding-left) * 1.5);
 `),p(`pending`,[h(`&::before`,`
 background-color: var(--n-option-color-pending);
 `)]),p(`selected`,`
 color: var(--n-option-text-color-active);
 `,[h(`&::before`,`
 background-color: var(--n-option-color-active);
 `),p(`pending`,[h(`&::before`,`
 background-color: var(--n-option-color-active-pending);
 `)])]),p(`disabled`,`
 cursor: not-allowed;
 `,[m(`selected`,`
 color: var(--n-option-text-color-disabled);
 `),p(`selected`,`
 opacity: var(--n-option-opacity-disabled);
 `)]),L(`check`,`
 font-size: 16px;
 position: absolute;
 right: calc(var(--n-option-padding-right) - 4px);
 top: calc(50% - 7px);
 color: var(--n-option-check-color);
 transition: color .3s var(--n-bezier);
 `,[Ae({enterScale:`0.5`})])])]);M(),D(),u();var et=[`tabindex`,`onFocusin`,`onFocusout`,`onKeyup`,`onKeydown`,`onMousedown`,`onMouseenter`,`onMouseleave`],tt=A({name:`InternalSelectMenu`,props:{...t.props,clsPrefix:{type:String,required:!0},scrollable:{type:Boolean,default:!0},treeMate:{type:Object,required:!0},multiple:Boolean,size:{type:String,default:`medium`},value:{type:[String,Number,Array],default:null},autoPending:Boolean,virtualScroll:{type:Boolean,default:!0},show:{type:Boolean,default:!0},labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},loading:Boolean,focusable:Boolean,renderLabel:Function,renderOption:Function,nodeProps:Function,showCheckmark:{type:Boolean,default:!0},onMousedown:Function,onScroll:Function,onFocus:Function,onBlur:Function,onKeyup:Function,onKeydown:Function,onTabOut:Function,onMouseenter:Function,onMouseleave:Function,onResize:Function,resetMenuOnOptionsChange:{type:Boolean,default:!0},inlineThemeDisabled:Boolean,scrollbarProps:Object,onToggle:Function},setup(n){let{mergedClsPrefixRef:r,mergedRtlRef:a,mergedComponentPropsRef:o}=c(n),s=ee(`InternalSelectMenu`,a,r),l=t(`InternalSelectMenu`,`-internal-select-menu`,$e,je,n,S(n,`clsPrefix`)),u=i(null),f=i(null),p=i(null),m=e(()=>n.treeMate.getFlattenedNodes()),h=e(()=>ce(m.value)),_=i(null);function y(){let{treeMate:e}=n,t=null,{value:r}=n;r===null?t=e.getFirstAvailableNode():(t=n.multiple?e.getNode((r||[])[(r||[]).length-1]):e.getNode(r),(!t||t.disabled)&&(t=e.getFirstAvailableNode())),H(t||null)}function b(){let{value:e}=_;e&&!n.treeMate.getNode(e.key)&&(_.value=null)}let x;I(()=>n.show,e=>{e?x=I(()=>n.treeMate,()=>{n.resetMenuOnOptionsChange?(n.autoPending?y():b(),N(U)):b()},{immediate:!0}):x?.()},{immediate:!0}),J(()=>{x?.()});let C=e(()=>d(l.value.self[v(`optionHeight`,n.size)])),w=e(()=>g(l.value.self[v(`padding`,n.size)])),T=e(()=>n.multiple&&Array.isArray(n.value)?new Set(n.value):new Set),E=e(()=>{let e=m.value;return e&&e.length===0}),D=e(()=>o?.value?.Select?.renderEmpty);function O(e){let{onToggle:t}=n;t&&t(e)}function k(e){let{onScroll:t}=n;t&&t(e)}function A(e){p.value?.sync(),k(e)}function j(){p.value?.sync()}function M(){let{value:e}=_;return e||null}function P(e,t){t.disabled||H(t,!1)}function F(e,t){t.disabled||O(t)}function L(e){oe(e,`action`)||n.onKeyup?.(e)}function R(e){oe(e,`action`)||n.onKeydown?.(e)}function z(e){n.onMousedown?.(e),!n.focusable&&e.preventDefault()}function B(){let{value:e}=_;e&&H(e.getNext({loop:!0}),!0)}function V(){let{value:e}=_;e&&H(e.getPrev({loop:!0}),!0)}function H(e,t=!1){_.value=e,t&&U()}function U(){let e=_.value;if(!e)return;let t=h.value(e.key);t!==null&&(n.virtualScroll?f.value?.scrollTo({index:t}):p.value?.scrollTo({index:t,elSize:C.value}))}function te(e){u.value?.contains(e.target)&&n.onFocus?.(e)}function W(e){u.value?.contains(e.relatedTarget)||n.onBlur?.(e)}K(Q,{handleOptionMouseEnter:P,handleOptionClick:F,valueSetRef:T,pendingTmNodeRef:_,nodePropsRef:S(n,`nodeProps`),showCheckmarkRef:S(n,`showCheckmark`),multipleRef:S(n,`multiple`),valueRef:S(n,`value`),renderLabelRef:S(n,`renderLabel`),renderOptionRef:S(n,`renderOption`),labelFieldRef:S(n,`labelField`),valueFieldRef:S(n,`valueField`)}),K(pe,u),q(()=>{let{value:e}=p;e&&e.sync()});let G=e(()=>{let{size:e}=n,{common:{cubicBezierEaseInOut:t},self:{height:r,borderRadius:i,color:a,groupHeaderTextColor:o,actionDividerColor:s,optionTextColorPressed:c,optionTextColor:u,optionTextColorDisabled:d,optionTextColorActive:f,optionOpacityDisabled:p,optionCheckColor:m,actionTextColor:h,optionColorPending:_,optionColorActive:y,loadingColor:b,loadingSize:x,optionColorActivePending:S,[v(`optionFontSize`,e)]:C,[v(`optionHeight`,e)]:w,[v(`optionPadding`,e)]:T}}=l.value;return{"--n-height":r,"--n-action-divider-color":s,"--n-action-text-color":h,"--n-bezier":t,"--n-border-radius":i,"--n-color":a,"--n-option-font-size":C,"--n-group-header-text-color":o,"--n-option-check-color":m,"--n-option-color-pending":_,"--n-option-color-active":y,"--n-option-color-active-pending":S,"--n-option-height":w,"--n-option-opacity-disabled":p,"--n-option-text-color":u,"--n-option-text-color-active":f,"--n-option-text-color-disabled":d,"--n-option-text-color-pressed":c,"--n-option-padding":T,"--n-option-padding-left":g(T,`left`),"--n-option-padding-right":g(T,`right`),"--n-loading-color":b,"--n-loading-size":x}}),{inlineThemeDisabled:re}=n,Y=re?ne(`internal-select-menu`,e(()=>n.size[0]),G,n):void 0,X={selfRef:u,next:B,prev:V,getPendingTmNode:M};return Ge(u,n.onResize),{mergedTheme:l,mergedClsPrefix:r,rtlEnabled:s,virtualListRef:f,scrollbarRef:p,itemSize:C,padding:w,flattenedNodes:m,empty:E,mergedRenderEmpty:D,virtualListContainer(){let{value:e}=f;return e?.listElRef},virtualListContent(){let{value:e}=f;return e?.itemsElRef},doScroll:k,handleFocusin:te,handleFocusout:W,handleKeyUp:L,handleKeyDown:R,handleMouseDown:z,handleVirtualListResize:j,handleVirtualListScroll:A,cssVars:re?void 0:G,themeClass:Y?.themeClass,onRender:Y?.onRender,...X}},render(){let{$slots:e,virtualScroll:t,clsPrefix:r,mergedTheme:i,themeClass:a,onRender:o}=this;return o?.(),n(),B(`div`,{ref:`selfRef`,tabindex:this.focusable?0:-1,class:y([`${r}-base-select-menu`,`${r}-base-select-menu--${this.size}-size`,this.rtlEnabled&&`${r}-base-select-menu--rtl`,a,this.multiple&&`${r}-base-select-menu--multiple`]),style:Y(this.cssVars),onFocusin:this.handleFocusin,onFocusout:this.handleFocusout,onKeyup:this.handleKeyUp,onKeydown:this.handleKeyDown,onMousedown:this.handleMouseDown,onMouseenter:this.onMouseenter,onMouseleave:this.onMouseleave},[x(()=>w(e.header,e=>e&&(n(),B(`div`,{class:y(`${r}-base-select-menu__header`),"data-header":!0,key:`header`},[x(()=>e)],2)))),this.loading?(n(),B(`div`,{key:0,class:y(`${r}-base-select-menu__loading`)},[(n(),U(Se,{clsPrefix:r,strokeWidth:20},null,8,[`clsPrefix`]))],2)):(n(),B(C,{key:1},[this.empty?(n(),B(`div`,{key:1,class:y(`${r}-base-select-menu__empty`),"data-empty":!0},[x(()=>P(e.empty,()=>[this.mergedRenderEmpty?.()||(n(),U(he,{theme:i.peers.Empty,themeOverrides:i.peerOverrides.Empty,size:this.size},null,8,[`theme`,`themeOverrides`,`size`]))]))],2)):(n(),U(E,F({key:0,ref:`scrollbarRef`,theme:i.peers.Scrollbar,themeOverrides:i.peerOverrides.Scrollbar,scrollable:this.scrollable,container:t?this.virtualListContainer:void 0,content:t?this.virtualListContent:void 0,onScroll:t?void 0:this.doScroll},this.scrollbarProps),{default:()=>t?(n(),U(Ue,{key:1,ref:`virtualListRef`,class:y(`${r}-virtual-list`),items:this.flattenedNodes,itemSize:this.itemSize,showScrollbar:!1,paddingTop:this.padding.top,paddingBottom:this.padding.bottom,onResize:this.handleVirtualListResize,onScroll:this.handleVirtualListScroll,itemResizable:!0},{default:({item:e})=>e.isGroup?(n(),U(qe,{key:e.key,clsPrefix:r,tmNode:e},null,8,[`clsPrefix`,`tmNode`])):e.ignored?null:(n(),U(Qe,{clsPrefix:r,key:e.key,tmNode:e},null,8,[`clsPrefix`,`tmNode`]))},1032,[`class`,`items`,`itemSize`,`paddingTop`,`paddingBottom`,`onResize`,`onScroll`])):(n(),B(`div`,{key:4,class:y(`${r}-base-select-menu-option-wrapper`),style:Y({paddingTop:this.padding.top,paddingBottom:this.padding.bottom})},[x(()=>this.flattenedNodes.map(e=>e.isGroup?(n(),U(qe,{key:e.key,clsPrefix:r,tmNode:e},null,8,[`clsPrefix`,`tmNode`])):(n(),U(Qe,{clsPrefix:r,key:e.key,tmNode:e},null,8,[`clsPrefix`,`tmNode`]))))],6))},1040,[`theme`,`themeOverrides`,`scrollable`,`container`,`content`,`onScroll`]))],64)),x(()=>w(e.action,e=>e&&[(n(),B(`div`,{class:y(`${r}-base-select-menu__action`),"data-action":!0,key:`action`},[x(()=>e)],2)),(n(),U(Ke,{onFocus:this.onTabOut,key:`focus-detector`},null,8,[`onFocus`]))]))],46,et)}});function nt(e){return e.type===`group`}function rt(e){return e.type===`ignored`}function it(e,t){try{return!!(1+t.toString().toLowerCase().indexOf(e.trim().toLowerCase()))}catch{return!1}}function at(e,t){return{getIsGroup:nt,getIgnored:rt,getKey(t){return nt(t)?t.name||t.key||`key-required`:t[e]},getChildren(e){return e[t]}}}function ot(e,t,n,r){if(!t)return e;function i(e){if(!Array.isArray(e))return[];let a=[];for(let o of e)if(nt(o)){let e=i(o[r]);e.length&&a.push(Object.assign({},o,{[r]:e}))}else if(rt(o))continue;else t(n,o)&&a.push(o);return a}return i(e)}function st(e,t,n){let r=new Map;return e.forEach(e=>{nt(e)?e[n].forEach(e=>{r.set(e[t],e)}):r.set(e[t],e)}),r}var ct=h([R(`base-selection`,`
 --n-padding-single: var(--n-padding-single-top) var(--n-padding-single-right) var(--n-padding-single-bottom) var(--n-padding-single-left);
 --n-padding-multiple: var(--n-padding-multiple-top) var(--n-padding-multiple-right) var(--n-padding-multiple-bottom) var(--n-padding-multiple-left);
 position: relative;
 z-index: auto;
 box-shadow: none;
 width: 100%;
 max-width: 100%;
 display: inline-block;
 vertical-align: bottom;
 border-radius: var(--n-border-radius);
 min-height: var(--n-height);
 line-height: 1.5;
 font-size: var(--n-font-size);
 `,[R(`base-loading`,`
 color: var(--n-loading-color);
 `),R(`base-selection-tags`,`min-height: var(--n-height);`),L(`border, state-border`,`
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 pointer-events: none;
 border: var(--n-border);
 border-radius: inherit;
 transition:
 box-shadow .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `),L(`state-border`,`
 z-index: 1;
 border-color: #0000;
 `),R(`base-suffix`,`
 cursor: pointer;
 position: absolute;
 top: 50%;
 transform: translateY(-50%);
 right: 10px;
 `,[L(`arrow`,`
 font-size: var(--n-arrow-size);
 color: var(--n-arrow-color);
 transition: color .3s var(--n-bezier);
 `)]),R(`base-selection-overlay`,`
 display: flex;
 align-items: center;
 white-space: nowrap;
 pointer-events: none;
 position: absolute;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 padding: var(--n-padding-single);
 transition: color .3s var(--n-bezier);
 `,[L(`wrapper`,`
 flex-basis: 0;
 flex-grow: 1;
 overflow: hidden;
 text-overflow: ellipsis;
 `)]),R(`base-selection-placeholder`,`
 color: var(--n-placeholder-color);
 `,[L(`inner`,`
 max-width: 100%;
 overflow: hidden;
 `)]),R(`base-selection-tags`,`
 cursor: pointer;
 outline: none;
 box-sizing: border-box;
 position: relative;
 z-index: auto;
 display: flex;
 padding: var(--n-padding-multiple);
 flex-wrap: wrap;
 align-items: center;
 width: 100%;
 vertical-align: bottom;
 background-color: var(--n-color);
 border-radius: inherit;
 transition:
 color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 `),R(`base-selection-label`,`
 height: var(--n-height);
 display: inline-flex;
 width: 100%;
 vertical-align: bottom;
 cursor: pointer;
 outline: none;
 z-index: auto;
 box-sizing: border-box;
 position: relative;
 transition:
 color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 border-radius: inherit;
 background-color: var(--n-color);
 align-items: center;
 `,[R(`base-selection-input`,`
 font-size: inherit;
 line-height: inherit;
 outline: none;
 cursor: pointer;
 box-sizing: border-box;
 border:none;
 width: 100%;
 padding: var(--n-padding-single);
 background-color: #0000;
 color: var(--n-text-color);
 transition: color .3s var(--n-bezier);
 caret-color: var(--n-caret-color);
 `,[L(`content`,`
 text-overflow: ellipsis;
 overflow: hidden;
 white-space: nowrap; 
 `)]),L(`render-label`,`
 color: var(--n-text-color);
 `)]),m(`disabled`,[h(`&:hover`,[L(`state-border`,`
 box-shadow: var(--n-box-shadow-hover);
 border: var(--n-border-hover);
 `)]),p(`focus`,[L(`state-border`,`
 box-shadow: var(--n-box-shadow-focus);
 border: var(--n-border-focus);
 `)]),p(`active`,[L(`state-border`,`
 box-shadow: var(--n-box-shadow-active);
 border: var(--n-border-active);
 `),R(`base-selection-label`,`background-color: var(--n-color-active);`),R(`base-selection-tags`,`background-color: var(--n-color-active);`)])]),p(`disabled`,`cursor: not-allowed;`,[L(`arrow`,`
 color: var(--n-arrow-color-disabled);
 `),R(`base-selection-label`,`
 cursor: not-allowed;
 background-color: var(--n-color-disabled);
 `,[R(`base-selection-input`,`
 cursor: not-allowed;
 color: var(--n-text-color-disabled);
 `),L(`render-label`,`
 color: var(--n-text-color-disabled);
 `)]),R(`base-selection-tags`,`
 cursor: not-allowed;
 background-color: var(--n-color-disabled);
 `),R(`base-selection-placeholder`,`
 cursor: not-allowed;
 color: var(--n-placeholder-color-disabled);
 `)]),R(`base-selection-input-tag`,`
 height: calc(var(--n-height) - 6px);
 line-height: calc(var(--n-height) - 6px);
 outline: none;
 display: none;
 position: relative;
 margin-bottom: 3px;
 max-width: 100%;
 vertical-align: bottom;
 `,[L(`input`,`
 font-size: inherit;
 font-family: inherit;
 min-width: 1px;
 padding: 0;
 background-color: #0000;
 outline: none;
 border: none;
 max-width: 100%;
 overflow: hidden;
 width: 1em;
 line-height: inherit;
 cursor: pointer;
 color: var(--n-text-color);
 caret-color: var(--n-caret-color);
 `),L(`mirror`,`
 position: absolute;
 left: 0;
 top: 0;
 white-space: pre;
 visibility: hidden;
 user-select: none;
 -webkit-user-select: none;
 opacity: 0;
 `)]),[`warning`,`error`].map(e=>p(`${e}-status`,[L(`state-border`,`border: var(--n-border-${e});`),m(`disabled`,[h(`&:hover`,[L(`state-border`,`
 box-shadow: var(--n-box-shadow-hover-${e});
 border: var(--n-border-hover-${e});
 `)]),p(`active`,[L(`state-border`,`
 box-shadow: var(--n-box-shadow-active-${e});
 border: var(--n-border-active-${e});
 `),R(`base-selection-label`,`background-color: var(--n-color-active-${e});`),R(`base-selection-tags`,`background-color: var(--n-color-active-${e});`)]),p(`focus`,[L(`state-border`,`
 box-shadow: var(--n-box-shadow-focus-${e});
 border: var(--n-border-focus-${e});
 `)])])]))]),R(`base-selection-popover`,`
 margin-bottom: -3px;
 display: flex;
 flex-wrap: wrap;
 margin-right: -8px;
 `),R(`base-selection-tag-wrapper`,`
 max-width: 100%;
 display: inline-flex;
 padding: 0 7px 3px 0;
 `,[h(`&:last-child`,`padding-right: 0;`),R(`tag`,`
 font-size: 14px;
 max-width: 100%;
 `,[L(`content`,`
 line-height: 1.25;
 text-overflow: ellipsis;
 overflow: hidden;
 `)])])]);M(),D(),u();var lt=[`disabled`,`value`,`autofocus`,`onBlur`,`onFocus`,`onKeydown`,`onInput`,`onCompositionstart`,`onCompositionend`],ut=[`tabindex`],dt=[`title`],ft=[`value`,`readonly`,`disabled`,`autofocus`,`onFocus`,`onBlur`,`onInput`,`onCompositionstart`,`onCompositionend`],pt=[`tabindex`],mt=[`onClick`,`onMouseenter`,`onMouseleave`,`onKeydown`,`onFocusin`,`onFocusout`,`onMousedown`],ht=A({name:`InternalSelection`,props:{...t.props,clsPrefix:{type:String,required:!0},bordered:{type:Boolean,default:void 0},active:Boolean,pattern:{type:String,default:``},placeholder:String,selectedOption:{type:Object,default:null},selectedOptions:{type:Array,default:null},labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},multiple:Boolean,filterable:Boolean,clearable:Boolean,disabled:Boolean,size:{type:String,default:`medium`},loading:Boolean,autofocus:Boolean,showArrow:{type:Boolean,default:!0},inputProps:Object,focused:Boolean,renderTag:Function,onKeydown:Function,onClick:Function,onBlur:Function,onFocus:Function,onDeleteOption:Function,maxTagCount:[String,Number],ellipsisTagPopoverProps:Object,onClear:Function,onPatternInput:Function,onPatternFocus:Function,onPatternBlur:Function,renderLabel:Function,status:String,inlineThemeDisabled:Boolean,ignoreComposition:{type:Boolean,default:!0},onResize:Function},setup(n){let{mergedClsPrefixRef:r,mergedRtlRef:a}=c(n),o=ee(`InternalSelection`,a,r),s=i(null),l=i(null),u=i(null),d=i(null),p=i(null),m=i(null),h=i(null),_=i(null),y=i(null),b=i(null),x=i(!1),C=i(!1),w=i(!1),T=t(`InternalSelection`,`-internal-selection`,ct,ke,n,S(n,`clsPrefix`)),E=e(()=>n.clearable&&!n.disabled&&(w.value||n.active)),D=e(()=>n.selectedOption?n.renderTag?n.renderTag({option:n.selectedOption,handleClose:()=>{}}):n.renderLabel?n.renderLabel(n.selectedOption,!0):Oe(n.selectedOption[n.labelField],n.selectedOption,!0):n.placeholder),O=e(()=>{let e=n.selectedOption;if(e)return e[n.labelField]}),k=e(()=>n.multiple?!!(Array.isArray(n.selectedOptions)&&n.selectedOptions.length):n.selectedOption!==null);function A(){let{value:e}=s;if(e){let{value:t}=l;t&&(t.style.width=`${e.offsetWidth}px`,n.maxTagCount!==`responsive`&&y.value?.sync({showAllItemsBeforeCalculate:!1}))}}function j(){let{value:e}=b;e&&(e.style.display=`none`)}function M(){let{value:e}=b;e&&(e.style.display=`inline-block`)}I(S(n,`active`),e=>{e||j()}),I(S(n,`pattern`),()=>{n.multiple&&N(A)});function P(e){let{onFocus:t}=n;t&&t(e)}function F(e){let{onBlur:t}=n;t&&t(e)}function L(e){let{onDeleteOption:t}=n;t&&t(e)}function R(e){let{onClear:t}=n;t&&t(e)}function z(e){let{onPatternInput:t}=n;t&&t(e)}function B(e){(!e.relatedTarget||!u.value?.contains(e.relatedTarget))&&P(e)}function V(e){u.value?.contains(e.relatedTarget)||F(e)}function H(e){R(e)}function U(){w.value=!0}function te(){w.value=!1}function W(e){n.active&&n.filterable&&e.target!==l.value&&e.preventDefault()}function G(e){L(e)}let K=i(!1);function re(e){if(e.key===`Backspace`&&!K.value&&!n.pattern.length){let{selectedOptions:e}=n;e?.length&&G(e[e.length-1])}}let J=null;function Y(e){let{value:t}=s;t&&(t.textContent=e.target.value,A()),n.ignoreComposition&&K.value?J=e:z(e)}function X(){K.value=!0}function Z(){K.value=!1,n.ignoreComposition&&z(J),J=null}function ie(e){C.value=!0,n.onPatternFocus?.(e)}function ae(e){C.value=!1,n.onPatternBlur?.(e)}function Q(){if(n.filterable)C.value=!1,m.value?.blur(),l.value?.blur();else if(n.multiple){let{value:e}=d;e?.blur()}else{let{value:e}=p;e?.blur()}}function oe(){n.filterable?(C.value=!1,m.value?.focus()):n.multiple?d.value?.focus():p.value?.focus()}function se(){let{value:e}=l;e&&(M(),e.focus())}function ce(){let{value:e}=l;e&&e.blur()}function le(e){let{value:t}=h;t&&t.setTextContent(`+${e}`)}function ue(){let{value:e}=_;return e}function de(){return l.value}let fe=null;function pe(){fe!==null&&window.clearTimeout(fe)}function me(){n.active||(pe(),fe=window.setTimeout(()=>{k.value&&(x.value=!0)},100))}function he(){pe()}function $(e){e||(pe(),x.value=!1)}I(k,e=>{e||(x.value=!1)}),q(()=>{f(()=>{let e=m.value;e&&(n.disabled?e.removeAttribute(`tabindex`):e.tabIndex=C.value?-1:0)})}),Ge(u,n.onResize);let{inlineThemeDisabled:ge}=n,_e=e(()=>{let{size:e}=n,{common:{cubicBezierEaseInOut:t},self:{fontWeight:r,borderRadius:i,color:a,placeholderColor:o,textColor:s,paddingSingle:c,paddingMultiple:l,caretColor:u,colorDisabled:d,textColorDisabled:f,placeholderColorDisabled:p,colorActive:m,boxShadowFocus:h,boxShadowActive:_,boxShadowHover:y,border:b,borderFocus:x,borderHover:S,borderActive:C,arrowColor:w,arrowColorDisabled:E,loadingColor:D,colorActiveWarning:O,boxShadowFocusWarning:k,boxShadowActiveWarning:A,boxShadowHoverWarning:j,borderWarning:ee,borderFocusWarning:M,borderHoverWarning:N,borderActiveWarning:P,colorActiveError:F,boxShadowFocusError:I,boxShadowActiveError:L,boxShadowHoverError:R,borderError:z,borderFocusError:B,borderHoverError:V,borderActiveError:H,clearColor:U,clearColorHover:te,clearColorPressed:W,clearSize:G,arrowSize:ne,[v(`height`,e)]:K,[v(`fontSize`,e)]:re}}=T.value,q=g(c),J=g(l);return{"--n-bezier":t,"--n-border":b,"--n-border-active":C,"--n-border-focus":x,"--n-border-hover":S,"--n-border-radius":i,"--n-box-shadow-active":_,"--n-box-shadow-focus":h,"--n-box-shadow-hover":y,"--n-caret-color":u,"--n-color":a,"--n-color-active":m,"--n-color-disabled":d,"--n-font-size":re,"--n-height":K,"--n-padding-single-top":q.top,"--n-padding-multiple-top":J.top,"--n-padding-single-right":q.right,"--n-padding-multiple-right":J.right,"--n-padding-single-left":q.left,"--n-padding-multiple-left":J.left,"--n-padding-single-bottom":q.bottom,"--n-padding-multiple-bottom":J.bottom,"--n-placeholder-color":o,"--n-placeholder-color-disabled":p,"--n-text-color":s,"--n-text-color-disabled":f,"--n-arrow-color":w,"--n-arrow-color-disabled":E,"--n-loading-color":D,"--n-color-active-warning":O,"--n-box-shadow-focus-warning":k,"--n-box-shadow-active-warning":A,"--n-box-shadow-hover-warning":j,"--n-border-warning":ee,"--n-border-focus-warning":M,"--n-border-hover-warning":N,"--n-border-active-warning":P,"--n-color-active-error":F,"--n-box-shadow-focus-error":I,"--n-box-shadow-active-error":L,"--n-box-shadow-hover-error":R,"--n-border-error":z,"--n-border-focus-error":B,"--n-border-hover-error":V,"--n-border-active-error":H,"--n-clear-size":G,"--n-clear-color":U,"--n-clear-color-hover":te,"--n-clear-color-pressed":W,"--n-arrow-size":ne,"--n-font-weight":r}}),ve=ge?ne(`internal-selection`,e(()=>n.size[0]),_e,n):void 0;return{mergedTheme:T,mergedClearable:E,mergedClsPrefix:r,rtlEnabled:o,patternInputFocused:C,filterablePlaceholder:D,label:O,selected:k,showTagsPanel:x,isComposing:K,counterRef:h,counterWrapperRef:_,patternInputMirrorRef:s,patternInputRef:l,selfRef:u,multipleElRef:d,singleElRef:p,patternInputWrapperRef:m,overflowRef:y,inputTagElRef:b,handleMouseDown:W,handleFocusin:B,handleClear:H,handleMouseEnter:U,handleMouseLeave:te,handleDeleteOption:G,handlePatternKeyDown:re,handlePatternInputInput:Y,handlePatternInputBlur:ae,handlePatternInputFocus:ie,handleMouseEnterCounter:me,handleMouseLeaveCounter:he,handleFocusout:V,handleCompositionEnd:Z,handleCompositionStart:X,onPopoverUpdateShow:$,focus:oe,focusInput:se,blur:Q,blurInput:ce,updateCounter:le,getCounter:ue,getTail:de,renderLabel:n.renderLabel,cssVars:ge?void 0:_e,themeClass:ve?.themeClass,onRender:ve?.onRender}},render(){let{status:e,multiple:t,size:r,disabled:i,filterable:a,maxTagCount:o,bordered:s,clsPrefix:c,ellipsisTagPopoverProps:l,onRender:u,renderTag:d,renderLabel:f}=this;u?.();let p=o===`responsive`,m=typeof o==`number`,h=p||m,g=(n(),U(te,null,{default:()=>(n(),U(Te,{clsPrefix:c,loading:this.loading,showArrow:this.showArrow,showClear:this.mergedClearable&&this.selected,onClear:this.handleClear},{default:()=>this.$slots.arrow?.()},1032,[`clsPrefix`,`loading`,`showArrow`,`showClear`,`onClear`]))},1024)),_;if(t){let{labelField:e}=this,t=t=>(n(),B(`div`,{class:y(`${c}-base-selection-tag-wrapper`),key:t.value},[d?(n(),B(C,{key:0},[x(()=>d({option:t,handleClose:()=>{this.handleDeleteOption(t)}}))],64)):(n(),U(we,{key:1,size:r,closable:!t.disabled,disabled:i,onClose:()=>{this.handleDeleteOption(t)},internalCloseIsButtonTag:!1,internalCloseFocusable:!1},{default:()=>f?f(t,!0):Oe(t[e],t,!0)},1032,[`size`,`closable`,`disabled`,`onClose`]))],2)),s=()=>(m?this.selectedOptions.slice(0,o):this.selectedOptions).map(t),u=a?(n(),B(`div`,{class:y(`${c}-base-selection-input-tag`),ref:`inputTagElRef`,key:`__input-tag__`},[j(`input`,F(this.inputProps,{ref:`patternInputRef`,tabindex:-1,disabled:i,value:this.pattern,autofocus:this.autofocus,class:`${c}-base-selection-input-tag__input`,onBlur:this.handlePatternInputBlur,onFocus:this.handlePatternInputFocus,onKeydown:this.handlePatternKeyDown,onInput:this.handlePatternInputInput,onCompositionstart:this.handleCompositionStart,onCompositionend:this.handleCompositionEnd}),null,16,lt),j(`span`,{ref:`patternInputMirrorRef`,class:y(`${c}-base-selection-input-tag__mirror`)},[x(()=>this.pattern)],2)],2)):null,v=p?()=>(n(),B(`div`,{class:y(`${c}-base-selection-tag-wrapper`),ref:`counterWrapperRef`},[(n(),U(we,{size:r,ref:`counterRef`,onMouseenter:this.handleMouseEnterCounter,onMouseleave:this.handleMouseLeaveCounter,disabled:i},null,8,[`size`,`onMouseenter`,`onMouseleave`,`disabled`]))],2)):void 0,b;if(m){let e=this.selectedOptions.length-o;e>0&&(b=(t=>(n(),B(`div`,{class:y(`${c}-base-selection-tag-wrapper`),key:`__counter__`},[(n(),U(we,{size:r,ref:`counterRef`,onMouseenter:this.handleMouseEnterCounter,disabled:i},{default:()=>`+${e}`},1032,[`size`,`onMouseenter`,`disabled`]))],2)))(b))}let S=p?a?(n(),U(Ce,{key:3,ref:`overflowRef`,updateCounter:this.updateCounter,getCounter:this.getCounter,getTail:this.getTail,style:{width:`100%`,display:`flex`,overflow:`hidden`}},{default:s,counter:v,tail:()=>u},1032,[`updateCounter`,`getCounter`,`getTail`])):(n(),U(Ce,{key:4,ref:`overflowRef`,updateCounter:this.updateCounter,getCounter:this.getCounter,style:{width:`100%`,display:`flex`,overflow:`hidden`}},{default:s,counter:v},1032,[`updateCounter`,`getCounter`])):m&&b?s().concat(b):s(),w=h?()=>(n(),B(`div`,{class:y(`${c}-base-selection-popover`)},[p?(n(),B(C,{key:0},[x(()=>s())],64)):(n(),B(C,{key:1},[x(()=>this.selectedOptions.map(t))],64))],2)):void 0,T=h?{show:this.showTagsPanel,trigger:`hover`,overlap:!0,placement:`top`,width:`trigger`,onUpdateShow:this.onPopoverUpdateShow,theme:this.mergedTheme.peers.Popover,themeOverrides:this.mergedTheme.peerOverrides.Popover,...l}:null,E=!this.selected&&(!this.active||!this.pattern&&!this.isComposing)?(n(),B(`div`,{key:5,class:y(`${c}-base-selection-placeholder ${c}-base-selection-overlay`)},[j(`div`,{class:y(`${c}-base-selection-placeholder__inner`)},[x(()=>this.placeholder)],2)],2)):null,D=a?(n(),B(`div`,{key:6,ref:`patternInputWrapperRef`,class:y(`${c}-base-selection-tags`)},[x(()=>S),p?x(()=>null):(n(),B(C,{key:1},[x(()=>u)],64)),x(()=>g)],2)):(n(),B(`div`,{key:7,ref:`multipleElRef`,class:y(`${c}-base-selection-tags`),tabindex:i?void 0:0},[x(()=>S),x(()=>g)],10,ut));_=(e=>(n(),B(C,{key:8},[h?(n(),U(ue,F({key:0},T,{scrollable:!0,style:`max-height: calc(var(--v-target-height) * 6.6);`}),{trigger:()=>D,default:w},1040)):(n(),B(C,{key:1},[x(()=>D)],64)),x(()=>E)],64)))(_)}else if(a){let e=this.pattern||this.isComposing,t=this.active?!e:!this.selected,r=!this.active&&this.selected;_=(e=>(n(),B(`div`,{key:9,ref:`patternInputWrapperRef`,class:y(`${c}-base-selection-label`),title:this.patternInputFocused?void 0:We(this.label)},[j(`input`,F(this.inputProps,{ref:`patternInputRef`,class:`${c}-base-selection-input`,value:this.active?this.pattern:``,placeholder:``,readonly:i,disabled:i,tabindex:-1,autofocus:this.autofocus,onFocus:this.handlePatternInputFocus,onBlur:this.handlePatternInputBlur,onInput:this.handlePatternInputInput,onCompositionstart:this.handleCompositionStart,onCompositionend:this.handleCompositionEnd}),null,16,ft),r?(n(),B(`div`,{class:y(`${c}-base-selection-label__render-label ${c}-base-selection-overlay`),key:`input`},[j(`div`,{class:y(`${c}-base-selection-overlay__wrapper`)},[d?(n(),B(C,{key:0},[x(()=>d({option:this.selectedOption,handleClose:()=>{}}))],64)):(n(),B(C,{key:1},[f?(n(),B(C,{key:0},[x(()=>f(this.selectedOption,!0))],64)):(n(),B(C,{key:1},[x(()=>Oe(this.label,this.selectedOption,!0))],64))],64))],2)],2)):x(()=>null),t?(n(),B(`div`,{class:y(`${c}-base-selection-placeholder ${c}-base-selection-overlay`),key:`placeholder`},[j(`div`,{class:y(`${c}-base-selection-overlay__wrapper`)},[x(()=>this.filterablePlaceholder)],2)],2)):x(()=>null),x(()=>g)],10,dt)))(_)}else _=(e=>(n(),B(`div`,{key:10,ref:`singleElRef`,class:y(`${c}-base-selection-label`),tabindex:this.disabled?void 0:0},[this.label===void 0?(n(),B(`div`,{class:y(`${c}-base-selection-placeholder ${c}-base-selection-overlay`),key:`placeholder`},[j(`div`,{class:y(`${c}-base-selection-placeholder__inner`)},[x(()=>this.placeholder)],2)],2)):(n(),B(`div`,{class:y(`${c}-base-selection-input`),title:We(this.label),key:`input`},[j(`div`,{class:y(`${c}-base-selection-input__content`)},[d?(n(),B(C,{key:0},[x(()=>d({option:this.selectedOption,handleClose:()=>{}}))],64)):(n(),B(C,{key:1},[f?(n(),B(C,{key:0},[x(()=>f(this.selectedOption,!0))],64)):(n(),B(C,{key:1},[x(()=>Oe(this.label,this.selectedOption,!0))],64))],64))],2)],10,[`title`])),x(()=>g)],10,pt)))(_);return n(),B(`div`,{ref:`selfRef`,class:y([`${c}-base-selection`,this.rtlEnabled&&`${c}-base-selection--rtl`,this.themeClass,e&&`${c}-base-selection--${e}-status`,{[`${c}-base-selection--active`]:this.active,[`${c}-base-selection--selected`]:this.selected||this.active&&this.pattern,[`${c}-base-selection--disabled`]:this.disabled,[`${c}-base-selection--multiple`]:this.multiple,[`${c}-base-selection--focus`]:this.focused}]),style:Y(this.cssVars),onClick:this.onClick,onMouseenter:this.handleMouseEnter,onMouseleave:this.handleMouseLeave,onKeydown:this.onKeydown,onFocusin:this.handleFocusin,onFocusout:this.handleFocusout,onMousedown:this.handleMouseDown},[x(()=>_),s?(n(),B(`div`,{key:0,class:y(`${c}-base-selection__border`)},null,2)):x(()=>null),s?(n(),B(`div`,{key:2,class:y(`${c}-base-selection__state-border`)},null,2)):x(()=>null)],46,mt)}}),gt=h([R(`select`,`
 z-index: auto;
 outline: none;
 width: 100%;
 position: relative;
 font-weight: var(--n-font-weight);
 `),R(`select-menu`,`
 margin: 4px 0;
 box-shadow: var(--n-menu-box-shadow);
 `,[Ae({originalTransition:`background-color .3s var(--n-bezier), box-shadow .3s var(--n-bezier)`})])]);o(),M(),u();var _t={...t.props,to:se.propTo,bordered:{type:Boolean,default:void 0},clearable:Boolean,clearCreatedOptionsOnClear:{type:Boolean,default:!0},clearFilterAfterSelect:{type:Boolean,default:!0},options:{type:Array,default:()=>[]},defaultValue:{type:[String,Number,Array],default:null},keyboard:{type:Boolean,default:!0},value:[String,Number,Array],placeholder:String,menuProps:Object,multiple:Boolean,size:String,menuSize:{type:String},filterable:Boolean,disabled:{type:Boolean,default:void 0},remote:Boolean,loading:Boolean,filter:Function,placement:{type:String,default:`bottom-start`},widthMode:{type:String,default:`trigger`},tag:Boolean,onCreate:Function,fallbackOption:{type:[Function,Boolean],default:void 0},show:{type:Boolean,default:void 0},showArrow:{type:Boolean,default:!0},maxTagCount:[Number,String],ellipsisTagPopoverProps:Object,consistentMenuWidth:{type:Boolean,default:!0},virtualScroll:{type:Boolean,default:!0},labelField:{type:String,default:`label`},valueField:{type:String,default:`value`},childrenField:{type:String,default:`children`},renderLabel:Function,renderOption:Function,renderTag:Function,"onUpdate:value":[Function,Array],inputProps:Object,nodeProps:Function,ignoreComposition:{type:Boolean,default:!0},showOnFocus:Boolean,onUpdateValue:[Function,Array],onBlur:[Function,Array],onClear:[Function,Array],onFocus:[Function,Array],onScroll:[Function,Array],onSearch:[Function,Array],onUpdateShow:[Function,Array],"onUpdate:show":[Function,Array],displayDirective:{type:String,default:`show`},resetMenuOnOptionsChange:{type:Boolean,default:!0},status:String,showCheckmark:{type:Boolean,default:!0},scrollbarProps:Object,onChange:[Function,Array],items:Array},vt=A({name:`Select`,props:_t,slots:Object,setup(n){let{mergedClsPrefixRef:r,mergedBorderedRef:a,namespaceRef:o,inlineThemeDisabled:l,mergedComponentPropsRef:u}=c(n),d=t(`Select`,`-select`,gt,Me,n,r),f=i(n.defaultValue),p=S(n,`value`),m=ye(p,f),h=i(!1),g=i(``),_=_e(n,[`items`,`options`]),v=i([]),y=i([]),b=e(()=>y.value.concat(v.value).concat(_.value)),x=e(()=>{let{filter:e}=n;if(e)return e;let{labelField:t,valueField:r}=n;return(e,n)=>{if(!n)return!1;let i=n[t];if(typeof i==`string`)return it(e,i);let a=n[r];return typeof a==`string`?it(e,a):typeof a==`number`&&it(e,String(a))}}),C=e(()=>{if(n.remote)return _.value;{let{value:e}=b,{value:t}=g;return!t.length||!n.filterable?e:ot(e,x.value,t,n.childrenField)}}),w=e(()=>{let{valueField:e,childrenField:t}=n,r=at(e,t);return fe(C.value,r)}),T=e(()=>st(b.value,n.valueField,n.childrenField)),E=i(!1),D=ye(S(n,`show`),E),O=i(null),k=i(null),A=i(null),{localeRef:j}=ie(`Select`),ee=e(()=>n.placeholder??j.value.placeholder),M=[],N=i(new Map),P=e(()=>{let{fallbackOption:e}=n;if(e===void 0){let{labelField:e,valueField:t}=n;return n=>({[e]:String(n),[t]:n})}return e===!1?!1:t=>Object.assign(e(t),{value:t})});function F(e){let t=n.remote,{value:r}=N,{value:i}=T,{value:a}=P,o=[];return e.forEach(e=>{if(i.has(e))o.push(i.get(e));else if(t&&r.has(e))o.push(r.get(e));else if(a){let t=a(e);t&&o.push(t)}}),o}let L=e(()=>{if(n.multiple){let{value:e}=m;return Array.isArray(e)?F(e):[]}return null}),R=e(()=>{let{value:e}=m;return!n.multiple&&!Array.isArray(e)?e===null?null:F([e])[0]||null:null}),z=be(n,{mergedSize:e=>{let{size:t}=n;if(t)return t;let{mergedSize:r}=e||{};return r?.value?r.value:u?.value?.Select?.size||`medium`}}),{mergedSizeRef:B,mergedDisabledRef:V,mergedStatusRef:H}=z;function U(e,t){let{onChange:r,"onUpdate:value":i,onUpdateValue:a}=n,{nTriggerFormChange:o,nTriggerFormInput:s}=z;r&&G(r,e,t),a&&G(a,e,t),i&&G(i,e,t),f.value=e,o(),s()}function te(e){let{onBlur:t}=n,{nTriggerFormBlur:r}=z;t&&G(t,e),r()}function W(){let{onClear:e}=n;e&&G(e)}function K(e){let{onFocus:t,showOnFocus:r}=n,{nTriggerFormFocus:i}=z;t&&G(t,e),i(),r&&X()}function re(e){let{onSearch:t}=n;t&&G(t,e)}function q(e){let{onScroll:t}=n;t&&G(t,e)}function J(){let{remote:e,multiple:t}=n;if(e){let{value:e}=N;if(t){let{valueField:t}=n;L.value?.forEach(n=>{e.set(n[t],n)})}else{let t=R.value;t&&e.set(t[n.valueField],t)}}}function Y(e){let{onUpdateShow:t,"onUpdate:show":r}=n;t&&G(t,e),r&&G(r,e),E.value=e}function X(){V.value||(Y(!0),E.value=!0,n.filterable&&Ne())}function Z(){Y(!1)}function ae(){g.value=``,y.value=M}let Q=i(!1);function ce(){n.filterable&&(Q.value=!0)}function le(){n.filterable&&(Q.value=!1,D.value||ae())}function ue(){V.value||(D.value?n.filterable?Ne():Z():X())}function de(e){A.value?.selfRef?.contains(e.relatedTarget)||(h.value=!1,te(e),Z())}function pe(e){K(e),h.value=!0}function me(){h.value=!0}function he(e){O.value?.$el.contains(e.relatedTarget)||(h.value=!1,te(e),Z())}function $(){O.value?.focus(),Z()}function ge(e){D.value&&(O.value?.$el.contains(s(e))||Z())}function ve(e){if(!Array.isArray(e))return[];if(P.value)return Array.from(e);{let{remote:t}=n,{value:r}=T;if(t){let{value:t}=N;return e.filter(e=>r.has(e)||t.has(e))}return e.filter(e=>r.has(e))}}function Se(e){Ce(e.rawNode)}function Ce(e){if(V.value)return;let{tag:t,remote:r,clearFilterAfterSelect:i,valueField:a}=n;if(t&&!r){let{value:e}=y,t=e[0]||null;if(t){let e=v.value;e.length?e.push(t):v.value=[t],y.value=M}}if(r&&N.value.set(e[a],e),n.multiple){let n=ve(m.value),o=n.findIndex(t=>t===e[a]);if(~o){if(n.splice(o,1),t&&!r){let t=we(e[a]);~t&&(v.value.splice(t,1),i&&(g.value=``))}}else n.push(e[a]),i&&(g.value=``);U(n,F(n))}else{if(t&&!r){let t=we(e[a]);~t?v.value=[v.value[t]]:v.value=M}je(),Z(),U(e[a],e)}}function we(e){return v.value.findIndex(t=>t[n.valueField]===e)}function Te(e){D.value||X();let{value:t}=e.target;g.value=t;let{tag:r,remote:i}=n;if(re(t),r&&!i){if(!t){y.value=M;return}let{onCreate:e}=n,r=e?e(t):{[n.labelField]:t,[n.valueField]:t},{valueField:i,labelField:a}=n;_.value.some(e=>e[i]===r[i]||e[a]===r[a])||v.value.some(e=>e[i]===r[i]||e[a]===r[a])?y.value=M:y.value=[r]}}function Ee(e){e.stopPropagation();let{multiple:t,tag:r,remote:i,clearCreatedOptionsOnClear:a}=n;!t&&n.filterable&&Z(),r&&!i&&a&&(v.value=M),W(),t?U([],[]):U(null,null)}function Oe(e){!oe(e,`action`)&&!oe(e,`empty`)&&!oe(e,`header`)&&e.preventDefault()}function ke(e){q(e)}function Ae(e){if(!n.keyboard){e.preventDefault();return}switch(e.key){case` `:if(n.filterable)break;e.preventDefault();case`Enter`:if(!O.value?.isComposing){if(D.value){let e=A.value?.getPendingTmNode();e?Se(e):n.filterable||(Z(),je())}else if(X(),n.tag&&Q.value){let e=y.value[0];if(e){let t=e[n.valueField],{value:r}=m;n.multiple&&Array.isArray(r)&&r.includes(t)||Ce(e)}}}e.preventDefault();break;case`ArrowUp`:if(e.preventDefault(),n.loading)return;D.value&&A.value?.prev();break;case`ArrowDown`:if(e.preventDefault(),n.loading)return;D.value?A.value?.next():X();break;case`Escape`:D.value&&(De(e),Z()),O.value?.focus()}}function je(){O.value?.focus()}function Ne(){O.value?.focusInput()}function Pe(){D.value&&k.value?.syncPosition()}J(),I(S(n,`options`),J);let Fe={focus:()=>{O.value?.focus()},focusInput:()=>{O.value?.focusInput()},blur:()=>{O.value?.blur()},blurInput:()=>{O.value?.blurInput()}},Ie=e(()=>{let{self:{menuBoxShadow:e}}=d.value;return{"--n-menu-box-shadow":e}}),Le=l?ne(`select`,void 0,Ie,n):void 0;return{...Fe,mergedStatus:H,mergedClsPrefix:r,mergedBordered:a,namespace:o,treeMate:w,isMounted:xe(),triggerRef:O,menuRef:A,pattern:g,uncontrolledShow:E,mergedShow:D,adjustedTo:se(n),uncontrolledValue:f,mergedValue:m,followerRef:k,localizedPlaceholder:ee,selectedOption:R,selectedOptions:L,mergedSize:B,mergedDisabled:V,focused:h,activeWithoutMenuOpen:Q,inlineThemeDisabled:l,onTriggerInputFocus:ce,onTriggerInputBlur:le,handleTriggerOrMenuResize:Pe,handleMenuFocus:me,handleMenuBlur:he,handleMenuTabOut:$,handleTriggerClick:ue,handleToggle:Se,handleDeleteOption:Ce,handlePatternInput:Te,handleClear:Ee,handleTriggerBlur:de,handleTriggerFocus:pe,handleKeydown:Ae,handleMenuAfterLeave:ae,handleMenuClickOutside:ge,handleMenuScroll:ke,handleMenuKeydown:Ae,handleMenuMousedown:Oe,mergedTheme:d,cssVars:l?void 0:Ie,themeClass:Le?.themeClass,onRender:Le?.onRender}},render(){return n(),B(`div`,{class:y(`${this.mergedClsPrefix}-select`)},[H(ae,null,{_:1,default:X(()=>[(n(),U(de,null,{_:1,default:X(()=>(n(),U(ht,{ref:`triggerRef`,inlineThemeDisabled:this.inlineThemeDisabled,status:this.mergedStatus,inputProps:this.inputProps,clsPrefix:this.mergedClsPrefix,showArrow:this.showArrow,maxTagCount:this.maxTagCount,ellipsisTagPopoverProps:this.ellipsisTagPopoverProps,bordered:this.mergedBordered,active:this.activeWithoutMenuOpen||this.mergedShow,pattern:this.pattern,placeholder:this.localizedPlaceholder,selectedOption:this.selectedOption,selectedOptions:this.selectedOptions,multiple:this.multiple,renderTag:this.renderTag,renderLabel:this.renderLabel,filterable:this.filterable,clearable:this.clearable,disabled:this.mergedDisabled,size:this.mergedSize,theme:this.mergedTheme.peers.InternalSelection,labelField:this.labelField,valueField:this.valueField,themeOverrides:this.mergedTheme.peerOverrides.InternalSelection,loading:this.loading,focused:this.focused,onClick:this.handleTriggerClick,onDeleteOption:this.handleDeleteOption,onPatternInput:this.handlePatternInput,onClear:this.handleClear,onBlur:this.handleTriggerBlur,onFocus:this.handleTriggerFocus,onKeydown:this.handleKeydown,onPatternBlur:this.onTriggerInputBlur,onPatternFocus:this.onTriggerInputFocus,onResize:this.handleTriggerOrMenuResize,ignoreComposition:this.ignoreComposition},{_:1,arrow:X(()=>[this.$slots.arrow?.()])},8,`inlineThemeDisabled.status.inputProps.clsPrefix.showArrow.maxTagCount.ellipsisTagPopoverProps.bordered.active.pattern.placeholder.selectedOption.selectedOptions.multiple.renderTag.renderLabel.filterable.clearable.disabled.size.theme.labelField.valueField.themeOverrides.loading.focused.onClick.onDeleteOption.onPatternInput.onClear.onBlur.onFocus.onKeydown.onPatternBlur.onPatternFocus.onResize.ignoreComposition`.split(`.`))))})),(n(),U(le,{ref:`followerRef`,show:this.mergedShow,to:this.adjustedTo,teleportDisabled:this.adjustedTo===se.tdkey,containerClass:this.namespace,width:this.consistentMenuWidth?`target`:void 0,minWidth:`target`,placement:this.placement},{_:1,default:X(()=>(n(),U(Z,{name:`fade-in-scale-up-transition`,appear:this.isMounted,onAfterLeave:this.handleMenuAfterLeave},{_:1,default:X(()=>this.mergedShow||this.displayDirective===`show`?(this.onRender?.(),_((n(),U(tt,F(this.menuProps,{ref:`menuRef`,onResize:this.handleTriggerOrMenuResize,inlineThemeDisabled:this.inlineThemeDisabled,virtualScroll:this.consistentMenuWidth&&this.virtualScroll,class:[`${this.mergedClsPrefix}-select-menu`,this.themeClass,this.menuProps?.class],clsPrefix:this.mergedClsPrefix,focusable:!0,labelField:this.labelField,valueField:this.valueField,autoPending:!0,nodeProps:this.nodeProps,theme:this.mergedTheme.peers.InternalSelectMenu,themeOverrides:this.mergedTheme.peerOverrides.InternalSelectMenu,treeMate:this.treeMate,multiple:this.multiple,size:this.menuSize,renderOption:this.renderOption,renderLabel:this.renderLabel,value:this.mergedValue,style:[this.menuProps?.style,this.cssVars],onToggle:this.handleToggle,onScroll:this.handleMenuScroll,onFocus:this.handleMenuFocus,onBlur:this.handleMenuBlur,onKeydown:this.handleMenuKeydown,onTabOut:this.handleMenuTabOut,onMousedown:this.handleMenuMousedown,show:this.mergedShow,showCheckmark:this.showCheckmark,resetMenuOnOptionsChange:this.resetMenuOnOptionsChange,scrollbarProps:this.scrollbarProps}),{_:1,empty:X(()=>[this.$slots.empty?.()]),header:X(()=>[this.$slots.header?.()]),action:X(()=>[this.$slots.action?.()])},16,`onResize.inlineThemeDisabled.virtualScroll.class.clsPrefix.labelField.valueField.nodeProps.theme.themeOverrides.treeMate.multiple.size.renderOption.renderLabel.value.style.onToggle.onScroll.onFocus.onBlur.onKeydown.onTabOut.onMousedown.show.showCheckmark.resetMenuOnOptionsChange.scrollbarProps`.split(`.`))),this.displayDirective===`show`?[[a,this.mergedShow],[Ee,this.handleMenuClickOutside,void 0,{capture:!0}]]:[[Ee,this.handleMenuClickOutside,void 0,{capture:!0}]])):null)},8,[`appear`,`onAfterLeave`])))},8,[`show`,`to`,`teleportDisabled`,`containerClass`,`width`,`placement`]))])})],2)}});export{Ke as a,Je as i,at as n,Ue as o,tt as r,vt as t};