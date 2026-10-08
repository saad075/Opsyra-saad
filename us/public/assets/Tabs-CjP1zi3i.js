import{$t as e,B as t,Bt as n,C as r,Cn as i,Et as a,Gn as o,Gt as s,Ht as c,K as l,L as u,Ln as d,Mn as f,Mt as p,Nt as m,Ot as h,P as g,Pn as _,Pt as v,Qt as y,R as b,St as x,U as S,V as C,Y as w,Yn as T,Yt as E,Z as D,_ as O,ar as k,cn as A,en as j,f as M,fn as N,gn as P,hn as F,jn as I,jt as L,kt as R,pn as z,r as B,rn as V,s as H,tn as U,un as ee,v as te,w as ne,wn as re,x as W,xn as ie,yr as G,yt as K}from"./is-browser-dQ-mLBB2.js";import{i as ae,n as oe,r as se,t as q}from"./cssr-DWJ_V8AC.js";import{t as ce}from"./use-merged-state-BNqaTJd4.js";import{t as le}from"./Button-BWtrbvO5.js";import{t as ue}from"./flatten-LMGZxwim.js";import{t as de}from"./ChevronRight-D9Z-dtB9.js";import{t as fe}from"./Add-BoEBmtkW.js";import{H as pe,N as me,l as he}from"./index-Cc8HQXPT.js";var ge=/\s/;function J(e){for(var t=e.length;t--&&ge.test(e.charAt(t)););return t}var Y=/^\s+/;function X(e){return e&&e.slice(0,J(e)+1).replace(Y,``)}var Z=NaN,_e=/^[-+]0x[0-9a-f]+$/i,ve=/^0b[01]+$/i,ye=/^0o[0-7]+$/i,Q=parseInt;function be(e){if(typeof e==`number`)return e;if(x(e))return Z;if(K(e)){var t=typeof e.valueOf==`function`?e.valueOf():e;e=K(t)?t+``:t}if(typeof e!=`string`)return e===0?e:+e;e=X(e);var n=ve.test(e);return n||ye.test(e)?Q(e.slice(2),n?2:8):_e.test(e)?Z:+e}var xe=function(){return a.Date.now()},Se=`Expected a function`,Ce=Math.max,we=Math.min;function $(e,t,n){var r,i,a,o,s,c,l=0,u=!1,d=!1,f=!0;if(typeof e!=`function`)throw TypeError(Se);t=be(t)||0,K(n)&&(u=!!n.leading,d=`maxWait`in n,a=d?Ce(be(n.maxWait)||0,t):a,f=`trailing`in n?!!n.trailing:f);function p(t){var n=r,a=i;return r=i=void 0,l=t,o=e.apply(a,n),o}function m(e){return l=e,s=setTimeout(_,t),u?p(e):o}function h(e){var n=e-c,r=e-l,i=t-n;return d?we(i,a-r):i}function g(e){var n=e-c,r=e-l;return c===void 0||n>=t||n<0||d&&r>=a}function _(){var e=xe();if(g(e))return v(e);s=setTimeout(_,h(e))}function v(e){return s=void 0,f&&r?p(e):(r=i=void 0,o)}function y(){s!==void 0&&clearTimeout(s),l=0,r=c=i=s=void 0}function b(){return s===void 0?o:v(xe())}function x(){var e=xe(),n=g(e);if(r=arguments,i=this,c=e,n){if(s===void 0)return m(c);if(d)return clearTimeout(s),s=setTimeout(_,t),p(c)}return s===void 0&&(s=setTimeout(_,t)),o}return x.cancel=y,x.flush=b,x}var Te=`Expected a function`;function Ee(e,t,n){var r=!0,i=!0;if(typeof e!=`function`)throw TypeError(Te);return K(n)&&(r=`leading`in n?!!n.leading:r,i=`trailing`in n?!!n.trailing:i),$(e,t,{leading:r,maxWait:t,trailing:i})}N(),d();var De=q(`.v-x-scroll`,{overflow:`auto`,scrollbarWidth:`none`},[q(`&::-webkit-scrollbar`,{width:0,height:0})]),Oe=A({name:`XScroll`,props:{disabled:Boolean,onScroll:Function},setup(){let e=o(null);function t(e){e.currentTarget.offsetWidth<e.currentTarget.scrollWidth&&e.deltaY!==0&&(e.currentTarget.scrollLeft+=e.deltaY+e.deltaX,e.preventDefault())}let n=S();return De.mount({id:`vueuc/x-scroll`,head:!0,anchorMetaName:oe,ssr:n}),Object.assign({selfRef:e,handleWheel:t},{scrollTo(...t){var n;(n=e.value)==null||n.scrollTo(...t)}})},render(){return ee(`div`,{ref:`selfRef`,onScroll:this.onScroll,onWheel:this.disabled?void 0:this.handleWheel,class:`v-x-scroll`},this.$slots)}});N();var ke=A({name:`ChevronLeft`,render(){return(()=>{let e=u(`dfe229c2639b2082`);return e[0]||=j(`svg`,{viewBox:`0 0 16 16`,fill:`none`,xmlns:`http://www.w3.org/2000/svg`},[j(`path`,{d:`M10.3536 3.14645C10.5488 3.34171 10.5488 3.65829 10.3536 3.85355L6.20711 8L10.3536 12.1464C10.5488 12.3417 10.5488 12.6583 10.3536 12.8536C10.1583 13.0488 9.84171 13.0488 9.64645 12.8536L5.14645 8.35355C4.95118 8.15829 4.95118 7.84171 5.14645 7.64645L9.64645 3.14645C9.84171 2.95118 10.1583 2.95118 10.3536 3.14645Z`,fill:`currentColor`})],-1)})()}}),Ae=w(`n-tabs`);N(),k();var je={tab:[String,Number,Object,Function],name:{type:[String,Number],required:!0},disabled:Boolean,displayDirective:{type:String,default:`if`},closable:{type:Boolean,default:void 0},tabProps:Object,label:[String,Number,Object,Function]},Me=A({__TAB_PANE__:!0,name:`TabPane`,alias:[`TabPanel`],props:je,slots:Object,setup(e){let t=z(Ae,null);return t||D(`tab-pane`,"`n-tab-pane` must be placed inside `n-tabs`."),{style:t.paneStyleRef,class:t.paneClassRef,mergedClsPrefix:t.mergedClsPrefixRef}},render(){return i(),V(`div`,{class:b([`${this.mergedClsPrefix}-tab-pane`,this.class]),style:G(this.style)},[C(()=>this.$slots.default?.())],6)}});N();var Ne=[`data-name`,`data-disabled`],Pe={internalLeftPadded:Boolean,internalAddable:Boolean,internalCreatedByPane:Boolean,...me(je,[`displayDirective`])},Fe=A({__TAB__:!0,inheritAttrs:!1,name:`Tab`,props:Pe,setup(t){let{mergedClsPrefixRef:n,valueRef:r,typeRef:i,closableRef:a,tabStyleRef:o,addTabStyleRef:s,tabClassRef:c,addTabClassRef:l,tabChangeIdRef:u,onBeforeLeaveRef:d,triggerRef:f,handleAdd:p,activateTab:m,handleClose:h}=z(Ae);return{trigger:f,mergedClosable:e(()=>{if(t.internalAddable)return!1;let{closable:e}=t;return e===void 0?a.value:e}),style:o,addStyle:s,tabClass:c,addTabClass:l,clsPrefix:n,value:r,type:i,handleClose(e){e.stopPropagation(),!t.disabled&&h(t.name)},activateTab(){if(t.disabled)return;if(t.internalAddable){p();return}let{name:e}=t,n=++u.id;if(e!==r.value){let{value:i}=d;i?Promise.resolve(i(t.name,r.value)).then(t=>{t&&u.id===n&&m(e)}):m(e)}}}},render(){let{internalAddable:e,clsPrefix:t,name:n,disabled:r,label:a,tab:o,value:s,mergedClosable:c,trigger:l,$slots:{default:u}}=this,d=a??o;return i(),V(`div`,{class:b(`${t}-tabs-tab-wrapper`)},[this.internalLeftPadded?(i(),V(`div`,{key:0,class:b(`${t}-tabs-tab-pad`)},null,2)):C(()=>null),(i(),V(`div`,F({key:n,"data-name":n,"data-disabled":r?!0:void 0},F({class:[`${t}-tabs-tab`,s===n&&`${t}-tabs-tab--active`,r&&`${t}-tabs-tab--disabled`,c&&`${t}-tabs-tab--closable`,e&&`${t}-tabs-tab--addable`,e?this.addTabClass:this.tabClass],onClick:l===`click`?this.activateTab:void 0,onMouseenter:l===`hover`?this.activateTab:void 0,style:e?this.addStyle:this.style},this.internalCreatedByPane?this.tabProps||{}:this.$attrs)),[j(`span`,{class:b(`${t}-tabs-tab__label`)},[e?(i(),V(E,{key:0},[j(`div`,{class:b(`${t}-tabs-tab__height-placeholder`)},`\xA0`,2),(i(),U(W,{clsPrefix:t},{default:()=>(i(),U(fe))},1032,[`clsPrefix`]))],64)):(i(),V(E,{key:1},[u?(i(),V(E,{key:0},[C(()=>u())],64)):(i(),V(E,{key:1},[typeof d==`object`?(i(),V(E,{key:0},[C(()=>d)],64)):(i(),V(E,{key:1},[C(()=>pe(d??n))],64))],64))],64))],2),c&&this.type===`card`?(i(),U(B,{key:0,clsPrefix:t,class:b(`${t}-tabs-tab__close`),onClick:this.handleClose,disabled:r},null,8,[`clsPrefix`,`class`,`onClick`,`disabled`])):C(()=>null)],16,Ne))],2)}}),Ie=R(`tabs`,`
 box-sizing: border-box;
 width: 100%;
 display: flex;
 flex-direction: column;
 transition:
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
`,[h(`&.transition-disabled`,[R(`tabs-tab`,`
 transition: none !important;
 `),R(`tabs-nav-scroll-content`,`
 transition: none !important;
 `),R(`tabs-tab-pad`,`
 transition: none !important;
 `)]),p(`segment-type`,[R(`tabs-rail`,[h(`&.transition-disabled`,[R(`tabs-capsule`,`
 transition: none;
 `)])])]),p(`top`,[R(`tab-pane`,`
 padding: var(--n-pane-padding-top) var(--n-pane-padding-right) var(--n-pane-padding-bottom) var(--n-pane-padding-left);
 `)]),p(`left`,[R(`tab-pane`,`
 padding: var(--n-pane-padding-right) var(--n-pane-padding-bottom) var(--n-pane-padding-left) var(--n-pane-padding-top);
 `)]),p(`left, right`,`
 flex-direction: row;
 `,[R(`tabs-bar`,`
 width: 2px;
 right: 0;
 transition:
 top .2s var(--n-bezier),
 max-height .2s var(--n-bezier),
 background-color .3s var(--n-bezier);
 `),R(`tabs-tab`,`
 padding: var(--n-tab-padding-vertical); 
 `)]),p(`right`,`
 flex-direction: row-reverse;
 `,[R(`tab-pane`,`
 padding: var(--n-pane-padding-left) var(--n-pane-padding-top) var(--n-pane-padding-right) var(--n-pane-padding-bottom);
 `),R(`tabs-bar`,`
 left: 0;
 `)]),p(`bottom`,`
 flex-direction: column-reverse;
 justify-content: flex-end;
 `,[R(`tab-pane`,`
 padding: var(--n-pane-padding-bottom) var(--n-pane-padding-right) var(--n-pane-padding-top) var(--n-pane-padding-left);
 `),R(`tabs-bar`,`
 top: 0;
 `)]),R(`tabs-rail`,`
 position: relative;
 padding: 3px;
 border-radius: var(--n-tab-border-radius);
 width: 100%;
 background-color: var(--n-color-segment);
 transition: background-color .3s var(--n-bezier);
 display: flex;
 align-items: center;
 `,[R(`tabs-capsule`,`
 border-radius: var(--n-tab-border-radius);
 position: absolute;
 left: 0;
 top: 0;
 pointer-events: none;
 background-color: var(--n-tab-color-segment);
 box-shadow: 0 1px 3px 0 rgba(0, 0, 0, .08);
 transition: transform 0.3s var(--n-bezier);
 `),R(`tabs-tab-wrapper`,`
 flex-basis: 0;
 flex-grow: 1;
 display: flex;
 align-items: center;
 justify-content: center;
 `,[R(`tabs-tab`,`
 overflow: hidden;
 border-radius: var(--n-tab-border-radius);
 width: 100%;
 display: flex;
 align-items: center;
 justify-content: center;
 `,[p(`active`,`
 font-weight: var(--n-font-weight-strong);
 color: var(--n-tab-text-color-active);
 `),h(`&:hover`,`
 color: var(--n-tab-text-color-hover);
 `)])])]),p(`flex`,[R(`tabs-nav`,`
 width: 100%;
 position: relative;
 `,[R(`tabs-wrapper`,`
 width: 100%;
 `,[R(`tabs-tab`,`
 margin-right: 0;
 `)])])]),R(`tabs-nav`,`
 box-sizing: border-box;
 line-height: 1.5;
 display: flex;
 transition: border-color .3s var(--n-bezier);
 `,[L(`prefix, suffix`,`
 display: flex;
 align-items: center;
 `),L(`prefix`,`padding-right: 16px;`),L(`suffix`,`padding-left: 16px;`)]),p(`top, bottom`,[h(`>`,[R(`tabs-nav`,[R(`tabs-nav-scroll-wrapper`,[h(`&::before`,`
 top: 0;
 bottom: 0;
 left: 0;
 width: 20px;
 `),h(`&::after`,`
 top: 0;
 bottom: 0;
 right: 0;
 width: 20px;
 `),p(`shadow-start`,[h(`&::before`,`
 box-shadow: inset 10px 0 8px -8px rgba(0, 0, 0, .12);
 `)]),p(`shadow-end`,[h(`&::after`,`
 box-shadow: inset -10px 0 8px -8px rgba(0, 0, 0, .12);
 `)])])])])]),p(`left, right`,[R(`tabs-nav-scroll-content`,`
 flex-direction: column;
 `),h(`>`,[R(`tabs-nav`,[R(`tabs-nav-scroll-wrapper`,[h(`&::before`,`
 top: 0;
 left: 0;
 right: 0;
 height: 20px;
 `),h(`&::after`,`
 bottom: 0;
 left: 0;
 right: 0;
 height: 20px;
 `),p(`shadow-start`,[h(`&::before`,`
 box-shadow: inset 0 10px 8px -8px rgba(0, 0, 0, .12);
 `)]),p(`shadow-end`,[h(`&::after`,`
 box-shadow: inset 0 -10px 8px -8px rgba(0, 0, 0, .12);
 `)])])])])]),R(`tabs-nav-scroll-wrapper`,`
 flex: 1;
 position: relative;
 overflow: hidden;
 `,[R(`tabs-nav-y-scroll`,`
 height: 100%;
 width: 100%;
 overflow-y: auto; 
 scrollbar-width: none;
 `,[h(`&::-webkit-scrollbar, &::-webkit-scrollbar-track-piece, &::-webkit-scrollbar-thumb`,`
 width: 0;
 height: 0;
 display: none;
 `)]),h(`&::before, &::after`,`
 transition: box-shadow .3s var(--n-bezier);
 pointer-events: none;
 content: "";
 position: absolute;
 z-index: 1;
 `),h(`&.transition-disabled`,[h(`&::before, &::after`,`
 transition: none;
 `)])]),R(`tabs-nav-scroll-content`,`
 display: flex;
 position: relative;
 min-width: 100%;
 min-height: 100%;
 width: fit-content;
 box-sizing: border-box;
 `),R(`tabs-wrapper`,`
 display: inline-flex;
 flex-wrap: nowrap;
 position: relative;
 `),R(`tabs-tab-wrapper`,`
 display: flex;
 flex-wrap: nowrap;
 flex-shrink: 0;
 flex-grow: 0;
 `),R(`tabs-tab`,`
 cursor: pointer;
 white-space: nowrap;
 flex-wrap: nowrap;
 display: inline-flex;
 align-items: center;
 color: var(--n-tab-text-color);
 font-size: var(--n-tab-font-size);
 background-clip: padding-box;
 padding: var(--n-tab-padding);
 transition:
 box-shadow .3s var(--n-bezier),
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[p(`disabled`,{cursor:`not-allowed`}),L(`close`,`
 margin-inline-start: 6px;
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 `),L(`label`,`
 display: flex;
 align-items: center;
 z-index: 1;
 `)]),R(`tabs-bar`,`
 position: absolute;
 bottom: 0;
 height: 2px;
 border-radius: 1px;
 background-color: var(--n-bar-color);
 transition:
 left .2s var(--n-bezier),
 max-width .2s var(--n-bezier),
 opacity .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 `,[h(`&.transition-disabled`,`
 transition: none;
 `),p(`disabled`,`
 background-color: var(--n-tab-text-color-disabled)
 `)]),R(`tabs-pane-wrapper`,`
 position: relative;
 overflow: hidden;
 transition: max-height .2s var(--n-bezier);
 `),R(`tab-pane`,`
 color: var(--n-pane-text-color);
 width: 100%;
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 opacity .2s var(--n-bezier);
 left: 0;
 right: 0;
 top: 0;
 `,[h(`&.next-transition-leave-active, &.prev-transition-leave-active, &.next-transition-enter-active, &.prev-transition-enter-active`,`
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 transform .2s var(--n-bezier),
 opacity .2s var(--n-bezier);
 `),h(`&.next-transition-leave-active, &.prev-transition-leave-active`,`
 position: absolute;
 `),h(`&.next-transition-enter-from, &.prev-transition-leave-to`,`
 transform: translateX(32px);
 opacity: 0;
 `),h(`&.next-transition-leave-to, &.prev-transition-enter-from`,`
 transform: translateX(-32px);
 opacity: 0;
 `),h(`&.next-transition-leave-from, &.next-transition-enter-to, &.prev-transition-leave-from, &.prev-transition-enter-to`,`
 transform: translateX(0);
 opacity: 1;
 `)]),R(`tabs-tab-pad`,`
 box-sizing: border-box;
 width: var(--n-tab-gap);
 flex-grow: 0;
 flex-shrink: 0;
 `),p(`line-type, bar-type`,[R(`tabs-tab`,`
 font-weight: var(--n-tab-font-weight);
 box-sizing: border-box;
 vertical-align: bottom;
 `,[h(`&:hover`,{color:`var(--n-tab-text-color-hover)`}),p(`active`,`
 color: var(--n-tab-text-color-active);
 font-weight: var(--n-tab-font-weight-active);
 `),p(`disabled`,{color:`var(--n-tab-text-color-disabled)`})])]),R(`tabs-nav`,[L(`prefix, suffix`,`
 border-color: var(--n-tab-border-color);
 `),R(`tabs-nav-scroll-content`,`
 border-color: var(--n-tab-border-color);
 `),p(`line-type`,[p(`top`,[L(`prefix, suffix`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),R(`tabs-nav-scroll-content`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),R(`tabs-bar`,`
 bottom: -1px;
 `)]),p(`left`,[L(`prefix, suffix`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),R(`tabs-nav-scroll-content`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),R(`tabs-bar`,`
 right: -1px;
 `)]),p(`right`,[L(`prefix, suffix`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),R(`tabs-nav-scroll-content`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),R(`tabs-bar`,`
 left: -1px;
 `)]),p(`bottom`,[L(`prefix, suffix`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),R(`tabs-nav-scroll-content`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),R(`tabs-bar`,`
 top: -1px;
 `)]),L(`prefix, suffix`,`
 transition: border-color .3s var(--n-bezier);
 `),R(`tabs-nav-scroll-content`,`
 transition: border-color .3s var(--n-bezier);
 `),R(`tabs-bar`,`
 border-radius: 0;
 `)]),p(`card-type`,[L(`prefix, suffix`,`
 transition: border-color .3s var(--n-bezier);
 `),R(`tabs-pad`,`
 flex-grow: 1;
 transition: border-color .3s var(--n-bezier);
 `),R(`tabs-tab-pad`,`
 transition: border-color .3s var(--n-bezier);
 `),R(`tabs-tab`,`
 font-weight: var(--n-tab-font-weight);
 border: 1px solid var(--n-tab-border-color);
 background-color: var(--n-tab-color);
 box-sizing: border-box;
 position: relative;
 vertical-align: bottom;
 display: flex;
 justify-content: space-between;
 font-size: var(--n-tab-font-size);
 color: var(--n-tab-text-color);
 `,[p(`addable`,`
 padding-left: 8px;
 padding-right: 8px;
 font-size: 16px;
 justify-content: center;
 `,[L(`height-placeholder`,`
 width: 0;
 font-size: var(--n-tab-font-size);
 `),m(`disabled`,[h(`&:hover`,`
 color: var(--n-tab-text-color-hover);
 `)])]),p(`closable`,`padding-inline-end: 8px;`),p(`active`,`
 background-color: #0000;
 font-weight: var(--n-tab-font-weight-active);
 color: var(--n-tab-text-color-active);
 `),p(`disabled`,`color: var(--n-tab-text-color-disabled);`)])]),p(`left, right`,`
 flex-direction: column; 
 `,[L(`prefix, suffix`,`
 padding: var(--n-tab-padding-vertical);
 `),R(`tabs-wrapper`,`
 flex-direction: column;
 `),R(`tabs-tab-wrapper`,`
 flex-direction: column;
 `,[R(`tabs-tab-pad`,`
 height: var(--n-tab-gap-vertical);
 width: 100%;
 `)])]),p(`top`,[p(`card-type`,[R(`tabs-scroll-padding`,`border-bottom: 1px solid var(--n-tab-border-color);`),L(`prefix, suffix`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),R(`tabs-tab`,`
 border-top-left-radius: var(--n-tab-border-radius);
 border-top-right-radius: var(--n-tab-border-radius);
 `,[p(`active`,`
 border-bottom: 1px solid #0000;
 `)]),R(`tabs-tab-pad`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `),R(`tabs-pad`,`
 border-bottom: 1px solid var(--n-tab-border-color);
 `)])]),p(`left`,[p(`card-type`,[R(`tabs-scroll-padding`,`border-right: 1px solid var(--n-tab-border-color);`),L(`prefix, suffix`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),R(`tabs-tab`,`
 border-top-left-radius: var(--n-tab-border-radius);
 border-bottom-left-radius: var(--n-tab-border-radius);
 `,[p(`active`,`
 border-right: 1px solid #0000;
 `)]),R(`tabs-tab-pad`,`
 border-right: 1px solid var(--n-tab-border-color);
 `),R(`tabs-pad`,`
 border-right: 1px solid var(--n-tab-border-color);
 `)])]),p(`right`,[p(`card-type`,[R(`tabs-scroll-padding`,`border-left: 1px solid var(--n-tab-border-color);`),L(`prefix, suffix`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),R(`tabs-tab`,`
 border-top-right-radius: var(--n-tab-border-radius);
 border-bottom-right-radius: var(--n-tab-border-radius);
 `,[p(`active`,`
 border-left: 1px solid #0000;
 `)]),R(`tabs-tab-pad`,`
 border-left: 1px solid var(--n-tab-border-color);
 `),R(`tabs-pad`,`
 border-left: 1px solid var(--n-tab-border-color);
 `)])]),p(`bottom`,[p(`card-type`,[R(`tabs-scroll-padding`,`border-top: 1px solid var(--n-tab-border-color);`),L(`prefix, suffix`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),R(`tabs-tab`,`
 border-bottom-left-radius: var(--n-tab-border-radius);
 border-bottom-right-radius: var(--n-tab-border-radius);
 `,[p(`active`,`
 border-top: 1px solid #0000;
 `)]),R(`tabs-tab-pad`,`
 border-top: 1px solid var(--n-tab-border-color);
 `),R(`tabs-pad`,`
 border-top: 1px solid var(--n-tab-border-color);
 `)])])]),R(`tabs-scroll-button`,[p(`start`,`
 padding-left: 10px;
 padding-right: 6px;
 `),p(`end`,`
 padding-right: 10px;
 padding-left: 6px;
 `),p(`up`,`
 padding-bottom: 10px;
 `),p(`down`,`
 padding-top: 10px;
 `)])]);N(),k();var Le=A({name:`TabsButton`,props:{type:{type:String,default:`next`},mergedClsPrefix:{type:String,required:!0},vertical:Boolean,disabled:Boolean,rtl:Boolean,theme:Object,themeOverrides:Object,onClick:Function},setup(e){return{handleClick:()=>{e.disabled||e.onClick?.(e.type)}}},render(){let{mergedClsPrefix:e,disabled:t,type:n,vertical:r,rtl:a,theme:o,themeOverrides:s,handleClick:c}=this,l=n===`next`,u=r?l:a?!l:l;return i(),U(le,{text:!0,disabled:t,size:`small`,theme:o,themeOverrides:s,onClick:c,class:b([`${e}-tabs-scroll-button`,!r&&n===`prev`&&`${e}-tabs-scroll-button--start`,!r&&n===`next`&&`${e}-tabs-scroll-button--end`,r&&n===`prev`&&`${e}-tabs-scroll-button--up`,r&&n===`next`&&`${e}-tabs-scroll-button--down`])},{icon:()=>(i(),U(W,{clsPrefix:e,style:G(r?{transform:`rotate(90deg)`}:void 0)},{default:()=>u?(i(),U(de,{key:1})):(i(),U(ke,{key:2}))},1032,[`clsPrefix`,`style`]))},1032,[`disabled`,`theme`,`themeOverrides`,`onClick`,`class`])}});N(),c(),k(),d();var Re=Ee,ze={...r.props,value:[String,Number],defaultValue:[String,Number],trigger:{type:String,default:`click`},type:{type:String,default:`bar`},closable:Boolean,justifyContent:String,size:String,placement:{type:String,default:`top`},tabStyle:[String,Object],tabClass:String,addTabStyle:[String,Object],addTabClass:String,barWidth:Number,paneClass:String,paneStyle:[String,Object],paneWrapperClass:String,paneWrapperStyle:[String,Object],addable:[Boolean,Object],tabsPadding:{type:Number,default:0},animated:Boolean,onBeforeLeave:Function,onAdd:Function,"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array],onClose:[Function,Array],labelSize:String,activeName:[String,Number],onActiveNameChange:[Function,Array],showScrollButton:Boolean,centerActiveTab:Boolean},Be=A({name:`Tabs`,props:ze,slots:Object,setup(t,{slots:n}){let{mergedClsPrefixRef:i,inlineThemeDisabled:a,mergedComponentPropsRef:s,mergedRtlRef:c}=l(t),u=M(`Tabs`,c,i),d=e(()=>{let{placement:e}=t;return e===`start`?u?.value?`right`:`left`:e===`end`?u?.value?`left`:`right`:e}),p=r(`Tabs`,`-tabs`,Ie,he,t,i),m=o(null),h=o(null),_=o(null),y=o(null),b=o(null),x=o(null),S=o(null),C=o(!0),w=o(!0),E=se(t,[`labelSize`,`size`]),D=e(()=>E.value?E.value:s?.value?.Tabs?.size||`medium`),O=se(t,[`activeName`,`value`]),k=o(O.value??t.defaultValue??(n.default?ue(n.default())[0]?.props?.name:null)),A=ce(O,k),j={id:0},N=e(()=>{if(t.justifyContent&&t.type!==`card`)return{display:`flex`,justifyContent:t.justifyContent}});I(A,()=>{j.id=0,B(),P(()=>{H()})});function F(){let{value:e}=A;return e===null?null:m.value?.querySelector(`[data-name="${e}"]`)}function L(e){if(t.type===`card`)return;let{value:n}=_;if(!n)return;let r=n.style.opacity===`0`;if(e){let a=`${i.value}-tabs-bar--disabled`,{barWidth:o}=t,s=d.value;if(e.dataset.disabled===`true`?n.classList.add(a):n.classList.remove(a),[`top`,`bottom`].includes(s)){if(z([`top`,`maxHeight`,`height`]),typeof o==`number`&&e.offsetWidth>=o){let t=Math.floor((e.offsetWidth-o)/2)+e.offsetLeft;n.style.left=`${t}px`,n.style.maxWidth=`${o}px`}else n.style.left=`${e.offsetLeft}px`,n.style.maxWidth=`${e.offsetWidth}px`;n.style.width=`8192px`,r&&(n.style.transition=`none`),n.offsetWidth,r&&(n.style.transition=``,n.style.opacity=`1`)}else{if(z([`left`,`maxWidth`,`width`]),typeof o==`number`&&e.offsetHeight>=o){let t=Math.floor((e.offsetHeight-o)/2)+e.offsetTop;n.style.top=`${t}px`,n.style.maxHeight=`${o}px`}else n.style.top=`${e.offsetTop}px`,n.style.maxHeight=`${e.offsetHeight}px`;n.style.height=`8192px`,r&&(n.style.transition=`none`),n.offsetHeight,r&&(n.style.transition=``,n.style.opacity=`1`)}}}function R(){if(t.type===`card`)return;let{value:e}=_;e&&(e.style.opacity=`0`)}function z(e){let{value:t}=_;if(t)for(let n of e)t.style[n]=``}function B(){if(t.type===`card`)return;let e=F();e?L(e):R()}function V(e,t,n,r){let i=e.getBoundingClientRect(),a=t.getBoundingClientRect(),o=n?`left`:`top`,s=n?`right`:`bottom`,c=0;r?c=(a[o]+a[s])/2-(i[o]+i[s])/2:a[o]<i[o]?c=a[o]-i[o]:a[s]>i[s]&&(c=a[s]-i[s]),c!==0&&e.scrollBy({[o]:c,behavior:`smooth`})}function H(){let e=[`top`,`bottom`].includes(d.value),n=F();if(n){if(e){let r=x.value?.$el;if(!r)return;V(r,n,e,t.centerActiveTab)}else{let{value:r}=S;if(!r)return;V(r,n,e,t.centerActiveTab)}}}let U=o(null),ee=0,W=null;function G(e){let t=U.value;if(t){ee=e.getBoundingClientRect().height;let n=`${ee}px`,r=()=>{t.style.height=n,t.style.maxHeight=n};W?(r(),W(),W=null):W=r}}function K(e){let t=U.value;if(t){let n=e.getBoundingClientRect().height,r=()=>{document.body.offsetHeight,t.style.maxHeight=`${n}px`,t.style.height=`${Math.max(ee,n)}px`};W?(W(),W=null,r()):W=r}}function oe(){let e=U.value;if(e){e.style.maxHeight=``,e.style.height=``;let{paneWrapperStyle:n}=t;if(typeof n==`string`)e.style.cssText=n;else if(n){let{maxHeight:t,height:r}=n;t!==void 0&&(e.style.maxHeight=t),r!==void 0&&(e.style.height=r)}}}let q={value:[]},le=o(`next`);function de(e){let t=A.value,n=`next`;for(let r of q.value){if(r===t)break;if(r===e){n=`prev`;break}}le.value=n,fe(e)}function fe(e){let{onActiveNameChange:n,onUpdateValue:r,"onUpdate:value":i}=t;n&&te(n,e),r&&te(r,e),i&&te(i,e),k.value=e}function pe(e){let{onClose:n}=t;n&&te(n,e)}function me(e){if([`top`,`bottom`].includes(d.value)){let{value:t}=x;if(!t)return;let n=t.$el;if(!n)return;let r=n.offsetWidth,i=!!u?.value,a=e===`next`?r:-r;n.scrollBy({left:i?-a:a,behavior:`smooth`})}else{let{value:t}=S;if(!t)return;let n=t.offsetHeight,r=e===`next`?t.scrollTop+n:t.scrollTop-n;t.scrollTo({top:r,left:0,behavior:`smooth`})}}let ge=!0;function J(){let{value:e}=_;if(!e)return;ge&&=!1;let t=`transition-disabled`;e.classList.add(t),B(),e.classList.remove(t)}let Y=o(null);function X({transitionDisabled:e}){let t=m.value;if(!t)return;e&&t.classList.add(`transition-disabled`);let n=F();n&&Y.value&&(Y.value.style.width=`${n.offsetWidth}px`,Y.value.style.height=`${n.offsetHeight}px`,Y.value.style.transform=`translate(${n.offsetLeft}px, ${n.offsetTop}px)`,e&&Y.value.offsetWidth),e&&t.classList.remove(`transition-disabled`)}I([A],()=>{t.type===`segment`&&P(()=>{X({transitionDisabled:!1})})}),ie(()=>{t.type===`segment`&&X({transitionDisabled:!0})});let Z=0;function _e(e){if(e.contentRect.width===0&&e.contentRect.height===0||Z===e.contentRect.width)return;Z=e.contentRect.width;let{type:n}=t;(n===`line`||n===`bar`)&&(ge||t.justifyContent?.startsWith(`space`))&&J(),n!==`segment`&&$(we())}let ve=Re(_e,64);function ye(){let{type:e}=t;e===`line`||e===`bar`?J():e===`segment`&&X({transitionDisabled:!0})}I([()=>t.justifyContent,()=>t.size],()=>{P(()=>{(t.type===`line`||t.type===`bar`)&&J()})}),I([d,()=>u?.value],()=>{P(()=>{ye(),$(we(),{instantly:!0})})}),I(()=>t.type,()=>{P(()=>{let e=h.value;e&&(e.classList.add(`transition-disabled`),ye(),e.offsetWidth,e.classList.remove(`transition-disabled`))})});let Q=o(!1);function be(e){let{target:t,contentRect:{width:n,height:r}}=e,i=t.parentElement.parentElement.offsetWidth,a=t.parentElement.parentElement.offsetHeight,o=d.value;if(!Q.value)o===`top`||o===`bottom`?i<n&&(Q.value=!0):a<r&&(Q.value=!0);else{let{value:e}=b;if(!e)return;o===`top`||o===`bottom`?i-n>e.$el.offsetWidth&&(Q.value=!1):a-r>e.$el.offsetHeight&&(Q.value=!1)}$(x.value?.$el||null)}let xe=Re(be,64);function Se(){let{onAdd:e}=t;e&&e()}let Ce=o(!1);function we(){let e=d.value;return(e===`top`||e===`bottom`?x.value?.$el:S.value)||null}function $(e,t={instantly:!1}){if(!e)return;let n=t.instantly?y.value:null;n&&n.classList.add(`transition-disabled`);let r=d.value;if(r===`top`||r===`bottom`){let{scrollLeft:t,scrollWidth:n,offsetWidth:r}=e,i=Math.abs(t);C.value=i<=1,w.value=i+r>=n-1,Ce.value=r<n-1}else{let{scrollTop:t,scrollHeight:n,offsetHeight:r}=e;C.value=t<=1,w.value=t+r>=n-1,Ce.value=r<n-1}n&&(n.offsetWidth,n.classList.remove(`transition-disabled`))}let Te=Re(e=>{$(e.target)},64);re(Ae,{triggerRef:T(t,`trigger`),tabStyleRef:T(t,`tabStyle`),tabClassRef:T(t,`tabClass`),addTabStyleRef:T(t,`addTabStyle`),addTabClassRef:T(t,`addTabClass`),paneClassRef:T(t,`paneClass`),paneStyleRef:T(t,`paneStyle`),mergedClsPrefixRef:i,typeRef:T(t,`type`),closableRef:T(t,`closable`),valueRef:A,tabChangeIdRef:j,onBeforeLeaveRef:T(t,`onBeforeLeave`),activateTab:de,handleClose:pe,handleAdd:Se}),ae(()=>{B(),H()}),f(()=>{let{value:e}=y;if(!e)return;let{value:t}=i,n=`${t}-tabs-nav-scroll-wrapper--shadow-start`,r=`${t}-tabs-nav-scroll-wrapper--shadow-end`;C.value?e.classList.remove(n):e.classList.add(n),w.value?e.classList.remove(r):e.classList.add(r)});let Ee={syncBarPosition:()=>{B()},scrollToCurrentTab:()=>{H()}},De=()=>{X({transitionDisabled:!0})},Oe=e(()=>{let{value:e}=D,{type:n}=t,r=`${e}${{card:`Card`,bar:`Bar`,line:`Line`,segment:`Segment`}[n]}`,{self:{barColor:i,closeIconColor:a,closeIconColorHover:o,closeIconColorPressed:s,tabColor:c,tabBorderColor:l,paneTextColor:u,tabFontWeight:d,tabBorderRadius:f,tabFontWeightActive:m,colorSegment:h,fontWeightStrong:_,tabColorSegment:y,closeSize:b,closeIconSize:x,closeColorHover:S,closeColorPressed:C,closeBorderRadius:w,[v(`panePadding`,e)]:T,[v(`tabPadding`,r)]:E,[v(`tabPaddingVertical`,r)]:O,[v(`tabGap`,r)]:k,[v(`tabGap`,`${r}Vertical`)]:A,[v(`tabTextColor`,n)]:j,[v(`tabTextColorActive`,n)]:M,[v(`tabTextColorHover`,n)]:N,[v(`tabTextColorDisabled`,n)]:P,[v(`tabFontSize`,e)]:F},common:{cubicBezierEaseInOut:I}}=p.value;return{"--n-bezier":I,"--n-color-segment":h,"--n-bar-color":i,"--n-tab-font-size":F,"--n-tab-text-color":j,"--n-tab-text-color-active":M,"--n-tab-text-color-disabled":P,"--n-tab-text-color-hover":N,"--n-pane-text-color":u,"--n-tab-border-color":l,"--n-tab-border-radius":f,"--n-close-size":b,"--n-close-icon-size":x,"--n-close-color-hover":S,"--n-close-color-pressed":C,"--n-close-border-radius":w,"--n-close-icon-color":a,"--n-close-icon-color-hover":o,"--n-close-icon-color-pressed":s,"--n-tab-color":c,"--n-tab-font-weight":d,"--n-tab-font-weight-active":m,"--n-tab-padding":E,"--n-tab-padding-vertical":O,"--n-tab-gap":k,"--n-tab-gap-vertical":A,"--n-pane-padding-left":g(T,`left`),"--n-pane-padding-right":g(T,`right`),"--n-pane-padding-top":g(T,`top`),"--n-pane-padding-bottom":g(T,`bottom`),"--n-font-weight-strong":_,"--n-tab-color-segment":y}}),ke=a?ne(`tabs`,e(()=>`${D.value[0]}${t.type[0]}`),Oe,t):void 0;return{mergedClsPrefix:i,mergedValue:A,renderedNames:new Set,segmentCapsuleElRef:Y,tabsPaneWrapperRef:U,tabsElRef:m,selfElRef:h,barElRef:_,addTabInstRef:b,xScrollInstRef:x,scrollWrapperElRef:y,addTabFixed:Q,tabWrapperStyle:N,handleNavResize:ve,mergedSize:D,handleScroll:Te,handleTabsResize:xe,cssVars:a?void 0:Oe,themeClass:ke?.themeClass,animationDirection:le,renderNameListRef:q,yScrollElRef:S,handleSegmentResize:De,onAnimationBeforeLeave:G,onAnimationEnter:K,onAnimationAfterEnter:oe,onRender:ke?.onRender,startReachedRef:C,endReachedRef:w,isOverflow:Ce,handleButtonClick:me,mergedTheme:p,rtlEnabled:u,mergedPlacement:d,...Ee}},render(){let{mergedClsPrefix:e,type:n,mergedPlacement:r,addTabFixed:a,addable:o,mergedSize:s,renderNameListRef:c,onRender:l,paneWrapperClass:u,paneWrapperStyle:d,startReachedRef:f,endReachedRef:p,isOverflow:m,showScrollButton:h,handleButtonClick:g,mergedTheme:_,rtlEnabled:v,$slots:{default:y,prefix:x,suffix:S}}=this;l?.();let w=y?ue(y()).filter(e=>e.type.__TAB_PANE__===!0):[],T=y?ue(y()).filter(e=>e.type.__TAB__===!0):[],D=!T.length,k=n===`card`,A=n===`segment`,M=!k&&!A&&this.justifyContent;c.value=[];let N=()=>{let n=(i(),V(`div`,{style:G(this.tabWrapperStyle),class:b(`${e}-tabs-wrapper`)},[M?C(()=>null):(i(),V(`div`,{key:1,class:b(`${e}-tabs-scroll-padding`),style:G(r===`top`||r===`bottom`?{width:`${this.tabsPadding}px`}:{height:`${this.tabsPadding}px`})},null,6)),D?(i(),V(E,{key:2},[C(()=>w.map((e,n)=>(c.value.push(e.props.name),We((i(),U(Fe,F(e.props,{internalCreatedByPane:!0,internalLeftPadded:n!==0&&(!M||M===`center`||M===`start`||M===`end`)}),t(e.children?{default:e.children.tab}:void 0),1040,[`internalLeftPadded`]))))))],64)):(i(),V(E,{key:3},[C(()=>T.map((e,t)=>(c.value.push(e.props.name),We(t!==0&&!M?Ue(e):e))))],64)),!a&&o&&k?(i(),V(E,{key:4},[C(()=>He(o,(D?w.length:T.length)!==0))],64)):C(()=>null),M?C(()=>null):(i(),V(`div`,{key:7,class:b(`${e}-tabs-scroll-padding`),style:G({width:`${this.tabsPadding}px`})},null,6)),k?C(()=>null):(i(),V(`div`,{key:9,ref:`barElRef`,class:b(`${e}-tabs-bar`)},null,2))],6));return i(),V(`div`,{ref:`tabsElRef`,class:b(`${e}-tabs-nav-scroll-content`)},[k&&o?(i(),U(H,{key:0,onResize:this.handleTabsResize},{default:()=>n},1032,[`onResize`])):(i(),V(E,{key:1},[C(()=>n)],64)),k?(i(),V(`div`,{key:2,class:b(`${e}-tabs-pad`)},null,2)):C(()=>null)],2)},P=A?`top`:r;return i(),V(`div`,{ref:`selfElRef`,class:b([`${e}-tabs`,this.themeClass,`${e}-tabs--${n}-type`,`${e}-tabs--${s}-size`,M&&`${e}-tabs--flex`,`${e}-tabs--${P}`,v&&`${e}-tabs--rtl`]),style:G(this.cssVars)},[j(`div`,{class:b([`${e}-tabs-nav--${n}-type`,`${e}-tabs-nav--${P}`,`${e}-tabs-nav`])},[C(()=>O(x,t=>t&&(i(),V(`div`,{class:b(`${e}-tabs-nav__prefix`)},[C(()=>t)],2)))),A?(i(),U(H,{key:0,onResize:this.handleSegmentResize},{default:()=>(i(),V(`div`,{class:b(`${e}-tabs-rail`),ref:`tabsElRef`},[j(`div`,{class:b(`${e}-tabs-capsule`),ref:`segmentCapsuleElRef`},[j(`div`,{class:b(`${e}-tabs-wrapper`)},[j(`div`,{class:b(`${e}-tabs-tab`)},null,2)],2)],2),D?(i(),V(E,{key:0},[C(()=>w.map((e,n)=>(c.value.push(e.props.name),i(),U(Fe,F(e.props,{internalCreatedByPane:!0,internalLeftPadded:n!==0}),t(e.children?{default:e.children.tab}:void 0),1040,[`internalLeftPadded`]))))],64)):(i(),V(E,{key:1},[C(()=>T.map((e,t)=>(c.value.push(e.props.name),t===0?e:Ue(e))))],64))],2))},1032,[`onResize`])):(i(),V(E,{key:1},[C(()=>h&&m&&(i(),U(Le,{mergedClsPrefix:e,type:`prev`,vertical:P===`left`||P===`right`,disabled:f,rtl:!!v,theme:_.peers.Button,themeOverrides:_.peerOverrides.Button,onClick:g},null,8,[`mergedClsPrefix`,`vertical`,`disabled`,`rtl`,`theme`,`themeOverrides`,`onClick`]))),(i(),U(H,{onResize:this.handleNavResize},{default:()=>(i(),V(`div`,{class:b(`${e}-tabs-nav-scroll-wrapper`),ref:`scrollWrapperElRef`},[[`top`,`bottom`].includes(P)?(i(),U(Oe,{key:0,ref:`xScrollInstRef`,onScroll:this.handleScroll},{default:N},1032,[`onScroll`])):(i(),V(`div`,{key:1,class:b(`${e}-tabs-nav-y-scroll`),onScroll:this.handleScroll,ref:`yScrollElRef`},[C(()=>N())],42,[`onScroll`]))],2))},1032,[`onResize`])),C(()=>h&&m&&(i(),U(Le,{mergedClsPrefix:e,type:`next`,vertical:P===`left`||P===`right`,disabled:p,rtl:!!v,theme:_.peers.Button,themeOverrides:_.peerOverrides.Button,onClick:g},null,8,[`mergedClsPrefix`,`vertical`,`disabled`,`rtl`,`theme`,`themeOverrides`,`onClick`])))],64)),a&&o&&k?(i(),V(E,{key:2},[C(()=>He(o,!0))],64)):C(()=>null),C(()=>O(S,t=>t&&(i(),V(`div`,{class:b(`${e}-tabs-nav__suffix`)},[C(()=>t)],2))))],2),C(()=>D&&(this.animated&&(P===`top`||P===`bottom`)?(i(),V(`div`,{key:1,ref:`tabsPaneWrapperRef`,style:G(d),class:b([`${e}-tabs-pane-wrapper`,u])},[C(()=>Ve(w,this.mergedValue,this.renderedNames,this.onAnimationBeforeLeave,this.onAnimationEnter,this.onAnimationAfterEnter,this.animationDirection))],6)):Ve(w,this.mergedValue,this.renderedNames)))],6)}});function Ve(e,t,r,a,o,c,l){let u=[];return e.forEach(e=>{let{name:n,displayDirective:i,"display-directive":a}=e.props,o=e=>i===e||a===e,c=t===n;if(e.key!==void 0&&(e.key=n),c||o(`show`)||o(`show:lazy`)&&r.has(n)){r.has(n)||r.add(n);let t=!o(`if`);u.push(t?_(e,[[s,c]]):e)}}),l?(i(),U(n,{name:`${l}-transition`,onBeforeLeave:a,onEnter:o,onAfterEnter:c},{default:()=>u},1032,[`name`,`onBeforeLeave`,`onEnter`,`onAfterEnter`])):u}function He(e,t){return i(),U(Fe,{ref:`addTabInstRef`,key:`__addable`,name:`__addable`,internalCreatedByPane:!0,internalAddable:!0,internalLeftPadded:t,disabled:typeof e==`object`&&e.disabled},null,8,[`internalLeftPadded`,`disabled`])}function Ue(e){let t=y(e);return t.props?t.props.internalLeftPadded=!0:t.props={internalLeftPadded:!0},t}function We(e){return Array.isArray(e.dynamicProps)?e.dynamicProps.includes(`internalLeftPadded`)||e.dynamicProps.push(`internalLeftPadded`):e.dynamicProps=[`internalLeftPadded`],e}export{Me as n,Be as t};