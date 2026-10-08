import{$t as e,B as t,C as n,Cn as r,Gn as i,Gt as a,Ht as o,K as s,Ln as c,Mn as l,Mt as u,Ot as d,Pn as f,R as p,V as m,W as h,Yn as g,Yt as _,Z as v,a as y,ar as b,cn as x,en as S,f as C,fn as w,hn as T,jn as E,jt as D,kt as O,l as k,pn as A,r as j,rn as M,tn as N,un as P,v as F,w as I,wn as L,yn as ee,yr as R,zt as z}from"./is-browser-dQ-mLBB2.js";import{t as B}from"./use-merged-state-BNqaTJd4.js";import{f as te}from"./Button-BWtrbvO5.js";import{t as V}from"./format-length-BwSo_EUP.js";import{$ as H,P as U,Q as W,X as G,Z as K,at as q,h as J,it as Y,ot as X,st as Z,y as Q,z as ne}from"./index-Cc8HQXPT.js";o(),w(),b(),c();var re=[`onMouseenter`,`onMouseleave`,`onMousedown`],ie={key:1,role:`none`},ae=x({name:`NDrawerContent`,inheritAttrs:!1,props:{blockScroll:Boolean,show:{type:Boolean,default:void 0},displayDirective:{type:String,required:!0},placement:{type:String,required:!0},contentClass:String,contentStyle:[Object,String],nativeScrollbar:{type:Boolean,required:!0},scrollbarProps:Object,trapFocus:{type:Boolean,default:!0},autoFocus:{type:Boolean,default:!0},showMask:{type:[Boolean,String],required:!0},maxWidth:Number,maxHeight:Number,minWidth:Number,minHeight:Number,resizable:Boolean,onClickoutside:Function,onAfterLeave:Function,onAfterEnter:Function,onEsc:Function},setup(t){let n=i(!!t.show),r=i(null),o=A(Z),c=0,u=``,d=null,f=i(!1),p=i(!1),m=e(()=>t.placement===`top`||t.placement===`bottom`),{mergedClsPrefixRef:h,mergedRtlRef:g}=s(t),_=C(`Drawer`,g,h),v=k,y=e=>{p.value=!0,c=m.value?e.clientY:e.clientX,u=document.body.style.cursor,document.body.style.cursor=m.value?`ns-resize`:`ew-resize`,document.body.addEventListener(`mousemove`,O),document.body.addEventListener(`mouseleave`,v),document.body.addEventListener(`mouseup`,k)},b=()=>{d!==null&&(window.clearTimeout(d),d=null),p.value?f.value=!0:d=window.setTimeout(()=>{f.value=!0},300)},x=()=>{d!==null&&(window.clearTimeout(d),d=null),f.value=!1},{doUpdateHeight:S,doUpdateWidth:w}=o,T=e=>{let{maxWidth:n}=t;if(n&&e>n)return n;let{minWidth:r}=t;return r&&e<r?r:e},D=e=>{let{maxHeight:n}=t;if(n&&e>n)return n;let{minHeight:r}=t;return r&&e<r?r:e};function O(e){if(p.value){if(m.value){let n=r.value?.offsetHeight||0,i=c-e.clientY;n+=t.placement===`bottom`?i:-i,n=D(n),S(n),c=e.clientY}else{let n=r.value?.offsetWidth||0,i=c-e.clientX;n+=t.placement===`right`?i:-i,n=T(n),w(n),c=e.clientX}}}function k(){p.value&&(c=0,p.value=!1,document.body.style.cursor=u,document.body.removeEventListener(`mousemove`,O),document.body.removeEventListener(`mouseup`,k),document.body.removeEventListener(`mouseleave`,v))}l(()=>{t.show&&(n.value=!0)}),E(()=>t.show,e=>{e||k()}),ee(()=>{k()});let j=e(()=>{let{show:e}=t,n=[[a,e]];return t.showMask||n.push([H,t.onClickoutside,void 0,{capture:!0}]),n});function M(){n.value=!1,t.onAfterLeave?.()}return ne(e(()=>t.blockScroll&&n.value)),L(X,r),L(Y,null),L(q,null),{bodyRef:r,rtlEnabled:_,mergedClsPrefix:o.mergedClsPrefixRef,isMounted:o.isMountedRef,mergedTheme:o.mergedThemeRef,displayed:n,transitionName:e(()=>({right:`slide-in-from-right-transition`,left:`slide-in-from-left-transition`,top:`slide-in-from-top-transition`,bottom:`slide-in-from-bottom-transition`})[t.placement]),handleAfterLeave:M,bodyDirectives:j,handleMousedownResizeTrigger:y,handleMouseenterResizeTrigger:b,handleMouseleaveResizeTrigger:x,isDragging:p,isHoverOnResizeTrigger:f}},render(){let{$slots:e,mergedClsPrefix:n}=this;return this.displayDirective===`show`||this.displayed||this.show?f((r(),M(`div`,ie,[(r(),N(G,{disabled:!this.showMask||!this.trapFocus,active:this.show,autoFocus:this.autoFocus,onEsc:this.onEsc},{default:()=>(r(),N(z,{name:this.transitionName,appear:this.isMounted,onAfterEnter:this.onAfterEnter,onAfterLeave:this.handleAfterLeave},{default:()=>f(P(`div`,T(this.$attrs,{role:`dialog`,ref:`bodyRef`,"aria-modal":`true`,class:[`${n}-drawer`,this.rtlEnabled&&`${n}-drawer--rtl`,`${n}-drawer--${this.placement}-placement`,this.isDragging&&`${n}-drawer--unselectable`,this.nativeScrollbar&&`${n}-drawer--native-scrollbar`]}),[this.resizable?(r(),M(`div`,{key:2,class:p([`${n}-drawer__resize-trigger`,(this.isDragging||this.isHoverOnResizeTrigger)&&`${n}-drawer__resize-trigger--hover`]),onMouseenter:this.handleMouseenterResizeTrigger,onMouseleave:this.handleMouseleaveResizeTrigger,onMousedown:this.handleMousedownResizeTrigger},null,42,re)):null,this.nativeScrollbar?(r(),M(`div`,{key:3,class:p([`${n}-drawer-content-wrapper`,this.contentClass]),style:R(this.contentStyle),role:`none`},[m(()=>e.default?.())],6)):(r(),N(y,T({key:4},this.scrollbarProps,{contentStyle:this.contentStyle,contentClass:[`${n}-drawer-content-wrapper`,this.contentClass],theme:this.mergedTheme.peers.Scrollbar,themeOverrides:this.mergedTheme.peerOverrides.Scrollbar}),t(e),1040,[`contentStyle`,`contentClass`,`theme`,`themeOverrides`]))]),this.bodyDirectives)},1032,[`name`,`appear`,`onAfterEnter`,`onAfterLeave`]))},1032,[`disabled`,`active`,`autoFocus`,`onEsc`]))])),[[a,this.displayDirective===`if`||this.displayed||this.show]]):null}}),{cubicBezierEaseIn:oe,cubicBezierEaseOut:se}=h;function ce({duration:e=`0.3s`,leaveDuration:t=`0.2s`,name:n=`slide-in-from-bottom`}={}){return[d(`&.${n}-transition-leave-active`,{transition:`transform ${t} ${oe}`}),d(`&.${n}-transition-enter-active`,{transition:`transform ${e} ${se}`}),d(`&.${n}-transition-enter-to`,{transform:`translateY(0)`}),d(`&.${n}-transition-enter-from`,{transform:`translateY(100%)`}),d(`&.${n}-transition-leave-from`,{transform:`translateY(0)`}),d(`&.${n}-transition-leave-to`,{transform:`translateY(100%)`})]}var{cubicBezierEaseIn:le,cubicBezierEaseOut:ue}=h;function de({duration:e=`0.3s`,leaveDuration:t=`0.2s`,name:n=`slide-in-from-left`}={}){return[d(`&.${n}-transition-leave-active`,{transition:`transform ${t} ${le}`}),d(`&.${n}-transition-enter-active`,{transition:`transform ${e} ${ue}`}),d(`&.${n}-transition-enter-to`,{transform:`translateX(0)`}),d(`&.${n}-transition-enter-from`,{transform:`translateX(-100%)`}),d(`&.${n}-transition-leave-from`,{transform:`translateX(0)`}),d(`&.${n}-transition-leave-to`,{transform:`translateX(-100%)`})]}var{cubicBezierEaseIn:$,cubicBezierEaseOut:fe}=h;function pe({duration:e=`0.3s`,leaveDuration:t=`0.2s`,name:n=`slide-in-from-right`}={}){return[d(`&.${n}-transition-leave-active`,{transition:`transform ${t} ${$}`}),d(`&.${n}-transition-enter-active`,{transition:`transform ${e} ${fe}`}),d(`&.${n}-transition-enter-to`,{transform:`translateX(0)`}),d(`&.${n}-transition-enter-from`,{transform:`translateX(100%)`}),d(`&.${n}-transition-leave-from`,{transform:`translateX(0)`}),d(`&.${n}-transition-leave-to`,{transform:`translateX(100%)`})]}var{cubicBezierEaseIn:me,cubicBezierEaseOut:he}=h;function ge({duration:e=`0.3s`,leaveDuration:t=`0.2s`,name:n=`slide-in-from-top`}={}){return[d(`&.${n}-transition-leave-active`,{transition:`transform ${t} ${me}`}),d(`&.${n}-transition-enter-active`,{transition:`transform ${e} ${he}`}),d(`&.${n}-transition-enter-to`,{transform:`translateY(0)`}),d(`&.${n}-transition-enter-from`,{transform:`translateY(-100%)`}),d(`&.${n}-transition-leave-from`,{transform:`translateY(0)`}),d(`&.${n}-transition-leave-to`,{transform:`translateY(-100%)`})]}var _e=d([O(`drawer`,`
 word-break: break-word;
 line-height: var(--n-line-height);
 position: absolute;
 pointer-events: all;
 box-shadow: var(--n-box-shadow);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 background-color: var(--n-color);
 color: var(--n-text-color);
 box-sizing: border-box;
 `,[pe(),de(),ge(),ce(),u(`unselectable`,`
 user-select: none; 
 -webkit-user-select: none;
 `),u(`native-scrollbar`,[O(`drawer-content-wrapper`,`
 overflow: auto;
 height: 100%;
 `)]),D(`resize-trigger`,`
 position: absolute;
 background-color: #0000;
 transition: background-color .3s var(--n-bezier);
 `,[u(`hover`,`
 background-color: var(--n-resize-trigger-color-hover);
 `)]),O(`drawer-content-wrapper`,`
 box-sizing: border-box;
 `),O(`drawer-content`,`
 height: 100%;
 display: flex;
 flex-direction: column;
 `,[u(`native-scrollbar`,[O(`drawer-body-content-wrapper`,`
 height: 100%;
 overflow: auto;
 `)]),O(`drawer-body`,`
 flex: 1 0 0;
 overflow: hidden;
 `),O(`drawer-body-content-wrapper`,`
 box-sizing: border-box;
 padding: var(--n-body-padding);
 `),O(`drawer-header`,`
 font-weight: var(--n-title-font-weight);
 line-height: 1;
 font-size: var(--n-title-font-size);
 color: var(--n-title-text-color);
 padding: var(--n-header-padding);
 transition: border .3s var(--n-bezier);
 border-bottom: 1px solid var(--n-divider-color);
 border-bottom: var(--n-header-border-bottom);
 display: flex;
 justify-content: space-between;
 align-items: center;
 `,[D(`main`,`
 flex: 1;
 `),D(`close`,`
 margin-left: 6px;
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 `)]),O(`drawer-footer`,`
 display: flex;
 justify-content: flex-end;
 border-top: var(--n-footer-border-top);
 transition: border .3s var(--n-bezier);
 padding: var(--n-footer-padding);
 `)]),u(`right-placement`,`
 top: 0;
 bottom: 0;
 right: 0;
 border-top-left-radius: var(--n-border-radius);
 border-bottom-left-radius: var(--n-border-radius);
 `,[D(`resize-trigger`,`
 width: 3px;
 height: 100%;
 top: 0;
 left: 0;
 transform: translateX(-1.5px);
 cursor: ew-resize;
 `)]),u(`left-placement`,`
 top: 0;
 bottom: 0;
 left: 0;
 border-top-right-radius: var(--n-border-radius);
 border-bottom-right-radius: var(--n-border-radius);
 `,[D(`resize-trigger`,`
 width: 3px;
 height: 100%;
 top: 0;
 right: 0;
 transform: translateX(1.5px);
 cursor: ew-resize;
 `)]),u(`top-placement`,`
 top: 0;
 left: 0;
 right: 0;
 border-bottom-left-radius: var(--n-border-radius);
 border-bottom-right-radius: var(--n-border-radius);
 `,[D(`resize-trigger`,`
 width: 100%;
 height: 3px;
 bottom: 0;
 left: 0;
 transform: translateY(1.5px);
 cursor: ns-resize;
 `)]),u(`bottom-placement`,`
 left: 0;
 bottom: 0;
 right: 0;
 border-top-left-radius: var(--n-border-radius);
 border-top-right-radius: var(--n-border-radius);
 `,[D(`resize-trigger`,`
 width: 100%;
 height: 3px;
 top: 0;
 left: 0;
 transform: translateY(-1.5px);
 cursor: ns-resize;
 `)])]),d(`body`,[d(`>`,[O(`drawer-container`,`
 position: fixed;
 `)])]),O(`drawer-container`,`
 position: relative;
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 pointer-events: none;
 `,[d(`> *`,`
 pointer-events: all;
 `)]),O(`drawer-mask`,`
 background-color: rgba(0, 0, 0, .3);
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `,[u(`invisible`,`
 background-color: rgba(0, 0, 0, 0)
 `),k({enterDuration:`0.2s`,leaveDuration:`0.2s`,enterCubicBezier:`var(--n-bezier-in)`,leaveCubicBezier:`var(--n-bezier-out)`})])]);o(),w(),b(),c();var ve=[`onClick`],ye={...n.props,show:Boolean,width:[Number,String],height:[Number,String],placement:{type:String,default:`right`},maskClosable:{type:Boolean,default:!0},showMask:{type:[Boolean,String],default:!0},to:[String,Object],displayDirective:{type:String,default:`if`},nativeScrollbar:{type:Boolean,default:!0},zIndex:Number,onMaskClick:Function,scrollbarProps:Object,contentClass:String,contentStyle:[Object,String],trapFocus:{type:Boolean,default:!0},onEsc:Function,autoFocus:{type:Boolean,default:!0},closeOnEsc:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!0},maxWidth:Number,maxHeight:Number,minWidth:Number,minHeight:Number,resizable:Boolean,defaultWidth:{type:[Number,String],default:251},defaultHeight:{type:[Number,String],default:251},onUpdateWidth:[Function,Array],onUpdateHeight:[Function,Array],"onUpdate:width":[Function,Array],"onUpdate:height":[Function,Array],"onUpdate:show":[Function,Array],onUpdateShow:[Function,Array],onAfterEnter:Function,onAfterLeave:Function,drawerStyle:[String,Object],drawerClass:String,target:null,onShow:Function,onHide:Function},be=x({name:`Drawer`,inheritAttrs:!1,props:ye,setup(t){let{mergedClsPrefixRef:r,namespaceRef:a,inlineThemeDisabled:o}=s(t),c=te(),l=n(`Drawer`,`-drawer`,_e,J,t,r),u=i(t.defaultWidth),d=i(t.defaultHeight),f=B(g(t,`width`),u),p=B(g(t,`height`),d),m=e(()=>{let{placement:e}=t;return e===`top`||e===`bottom`?``:V(f.value)}),h=e(()=>{let{placement:e}=t;return e===`left`||e===`right`?``:V(p.value)}),_=e=>{let{onUpdateWidth:n,"onUpdate:width":r}=t;n&&F(n,e),r&&F(r,e),u.value=e},v=e=>{let{onUpdateHeight:n,"onUpdate:width":r}=t;n&&F(n,e),r&&F(r,e),d.value=e},y=e(()=>[{width:m.value,height:h.value},t.drawerStyle||``]);function b(e){let{onMaskClick:n,maskClosable:r}=t;r&&w(!1),n&&n(e)}function x(e){b(e)}let S=Q();function C(e){t.onEsc?.(),t.show&&t.closeOnEsc&&U(e)&&(S.value||w(!1))}function w(e){let{onHide:n,onUpdateShow:r,"onUpdate:show":i}=t;r&&F(r,e),i&&F(i,e),n&&!e&&F(n,e)}L(Z,{isMountedRef:c,mergedThemeRef:l,mergedClsPrefixRef:r,doUpdateShow:w,doUpdateHeight:v,doUpdateWidth:_});let T=e(()=>{let{common:{cubicBezierEaseInOut:e,cubicBezierEaseIn:t,cubicBezierEaseOut:n},self:{color:r,textColor:i,boxShadow:a,lineHeight:o,headerPadding:s,footerPadding:c,borderRadius:u,bodyPadding:d,titleFontSize:f,titleTextColor:p,titleFontWeight:m,headerBorderBottom:h,footerBorderTop:g,closeIconColor:_,closeIconColorHover:v,closeIconColorPressed:y,closeColorHover:b,closeColorPressed:x,closeIconSize:S,closeSize:C,closeBorderRadius:w,resizableTriggerColorHover:T}}=l.value;return{"--n-line-height":o,"--n-color":r,"--n-border-radius":u,"--n-text-color":i,"--n-box-shadow":a,"--n-bezier":e,"--n-bezier-out":n,"--n-bezier-in":t,"--n-header-padding":s,"--n-body-padding":d,"--n-footer-padding":c,"--n-title-text-color":p,"--n-title-font-size":f,"--n-title-font-weight":m,"--n-header-border-bottom":h,"--n-footer-border-top":g,"--n-close-icon-color":_,"--n-close-icon-color-hover":v,"--n-close-icon-color-pressed":y,"--n-close-size":C,"--n-close-color-hover":b,"--n-close-color-pressed":x,"--n-close-icon-size":S,"--n-close-border-radius":w,"--n-resize-trigger-color-hover":T}}),E=o?I(`drawer`,void 0,T,t):void 0;return{mergedClsPrefix:r,namespace:a,mergedBodyStyle:y,handleOutsideClick:x,handleMaskClick:b,handleEsc:C,mergedTheme:l,cssVars:o?void 0:T,themeClass:E?.themeClass,onRender:E?.onRender,isMounted:c}},render(){let{mergedClsPrefix:e}=this;return r(),N(K,{to:this.to,show:this.show},{default:()=>(this.onRender?.(),f((r(),M(`div`,{class:p([`${e}-drawer-container`,this.namespace,this.themeClass]),style:R(this.cssVars),role:`none`},[this.showMask?(r(),N(z,{key:0,name:`fade-in-transition`,appear:this.isMounted},{default:()=>this.show?(r(),M(`div`,{key:1,"aria-hidden":!0,class:p([`${e}-drawer-mask`,this.showMask===`transparent`&&`${e}-drawer-mask--invisible`]),onClick:this.handleMaskClick},null,10,ve)):null},1032,[`appear`])):m(()=>null),(r(),N(ae,T(this.$attrs,{class:[this.drawerClass,this.$attrs.class],style:[this.mergedBodyStyle,this.$attrs.style],blockScroll:this.blockScroll,contentStyle:this.contentStyle,contentClass:this.contentClass,placement:this.placement,scrollbarProps:this.scrollbarProps,show:this.show,displayDirective:this.displayDirective,nativeScrollbar:this.nativeScrollbar,onAfterEnter:this.onAfterEnter,onAfterLeave:this.onAfterLeave,trapFocus:this.trapFocus,autoFocus:this.autoFocus,resizable:this.resizable,maxHeight:this.maxHeight,minHeight:this.minHeight,maxWidth:this.maxWidth,minWidth:this.minWidth,showMask:this.showMask,onEsc:this.handleEsc,onClickoutside:this.handleOutsideClick}),t(this.$slots),1040,[`class`,`style`,`blockScroll`,`contentStyle`,`contentClass`,`placement`,`scrollbarProps`,`show`,`displayDirective`,`nativeScrollbar`,`onAfterEnter`,`onAfterLeave`,`trapFocus`,`autoFocus`,`resizable`,`maxHeight`,`minHeight`,`maxWidth`,`minWidth`,`showMask`,`onEsc`,`onClickoutside`]))],6)),[[W,{zIndex:this.zIndex,enabled:this.show}]]))},1032,[`to`,`show`])}});w(),b();var xe=x({name:`DrawerContent`,props:{title:String,headerClass:String,headerStyle:[Object,String],footerClass:String,footerStyle:[Object,String],bodyClass:String,bodyStyle:[Object,String],bodyContentClass:String,bodyContentStyle:[Object,String],nativeScrollbar:{type:Boolean,default:!0},scrollbarProps:Object,closable:Boolean},slots:Object,setup(){let e=A(Z,null);e||v(`drawer-content`,"`n-drawer-content` must be placed inside `n-drawer`.");let{doUpdateShow:t}=e;function n(){t(!1)}return{handleCloseClick:n,mergedTheme:e.mergedThemeRef,mergedClsPrefix:e.mergedClsPrefixRef}},render(){let{title:e,mergedClsPrefix:n,nativeScrollbar:i,mergedTheme:a,bodyClass:o,bodyStyle:s,bodyContentClass:c,bodyContentStyle:l,headerClass:u,headerStyle:d,footerClass:f,footerStyle:h,scrollbarProps:g,closable:v,$slots:b}=this;return r(),M(`div`,{role:`none`,class:p([`${n}-drawer-content`,i&&`${n}-drawer-content--native-scrollbar`])},[b.header||e||v?(r(),M(`div`,{key:0,class:p([`${n}-drawer-header`,u]),style:R(d),role:`none`},[S(`div`,{class:p(`${n}-drawer-header__main`),role:`heading`,"aria-level":`1`},[b.header===void 0?(r(),M(_,{key:1},[m(()=>e)],64)):(r(),M(_,{key:0},[m(()=>b.header())],64))],2),m(()=>v&&(r(),N(j,{onClick:this.handleCloseClick,clsPrefix:n,class:p(`${n}-drawer-header__close`),absolute:!0},null,8,[`onClick`,`clsPrefix`,`class`])))],6)):m(()=>null),i?(r(),M(`div`,{key:2,class:p([`${n}-drawer-body`,o]),style:R(s),role:`none`},[S(`div`,{class:p([`${n}-drawer-body-content-wrapper`,c]),style:R(l),role:`none`},[m(()=>b.default?.())],6)],6)):(r(),N(y,T({key:3,themeOverrides:a.peerOverrides.Scrollbar,theme:a.peers.Scrollbar},g,{class:`${n}-drawer-body`,contentClass:[`${n}-drawer-body-content-wrapper`,c],contentStyle:l}),t(b),1040,[`themeOverrides`,`theme`,`class`,`contentClass`,`contentStyle`])),b.footer?(r(),M(`div`,{key:4,class:p([`${n}-drawer-footer`,f]),style:R(h),role:`none`},[m(()=>b.footer())],6)):m(()=>null)],2)}});export{be as n,xe as t};