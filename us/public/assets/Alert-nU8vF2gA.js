import{$t as e,C as t,Cn as n,D as r,Gn as i,K as a,Ln as o,Mt as s,O as c,Ot as l,P as u,Pt as d,R as f,V as p,_ as m,cn as h,en as g,f as _,fn as v,h as y,hn as b,jt as x,k as S,kt as C,n as w,r as T,rn as E,tn as D,w as O,x as k}from"./is-browser-dQ-mLBB2.js";import{G as A,J as j,K as M,U as N,W as P,q as F}from"./index-Cc8HQXPT.js";function I(e){let{lineHeight:t,borderRadius:n,fontWeightStrong:r,baseColor:i,dividerColor:a,actionColor:o,textColor1:s,textColor2:l,closeColorHover:u,closeColorPressed:d,closeIconColor:f,closeIconColorHover:p,closeIconColorPressed:m,infoColor:h,successColor:g,warningColor:_,errorColor:v,fontSize:y}=e;return{...j,fontSize:y,lineHeight:t,titleFontWeight:r,borderRadius:n,border:`1px solid ${a}`,color:o,titleTextColor:s,iconColor:l,contentTextColor:l,closeBorderRadius:n,closeColorHover:u,closeColorPressed:d,closeIconColor:f,closeIconColorHover:p,closeIconColorPressed:m,borderInfo:`1px solid ${S(i,c(h,{alpha:.25}))}`,colorInfo:S(i,c(h,{alpha:.08})),titleTextColorInfo:s,iconColorInfo:h,contentTextColorInfo:l,closeColorHoverInfo:u,closeColorPressedInfo:d,closeIconColorInfo:f,closeIconColorHoverInfo:p,closeIconColorPressedInfo:m,borderSuccess:`1px solid ${S(i,c(g,{alpha:.25}))}`,colorSuccess:S(i,c(g,{alpha:.08})),titleTextColorSuccess:s,iconColorSuccess:g,contentTextColorSuccess:l,closeColorHoverSuccess:u,closeColorPressedSuccess:d,closeIconColorSuccess:f,closeIconColorHoverSuccess:p,closeIconColorPressedSuccess:m,borderWarning:`1px solid ${S(i,c(_,{alpha:.33}))}`,colorWarning:S(i,c(_,{alpha:.08})),titleTextColorWarning:s,iconColorWarning:_,contentTextColorWarning:l,closeColorHoverWarning:u,closeColorPressedWarning:d,closeIconColorWarning:f,closeIconColorHoverWarning:p,closeIconColorPressedWarning:m,borderError:`1px solid ${S(i,c(v,{alpha:.25}))}`,colorError:S(i,c(v,{alpha:.08})),titleTextColorError:s,iconColorError:v,contentTextColorError:l,closeColorHoverError:u,closeColorPressedError:d,closeIconColorError:f,closeIconColorHoverError:p,closeIconColorPressedError:m}}var L={name:`Alert`,common:r,self:I},R=C(`alert`,`
 line-height: var(--n-line-height);
 border-radius: var(--n-border-radius);
 position: relative;
 transition: background-color .3s var(--n-bezier);
 background-color: var(--n-color);
 text-align: start;
 word-break: break-word;
`,[x(`border`,`
 border-radius: inherit;
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 transition: border-color .3s var(--n-bezier);
 border: var(--n-border);
 pointer-events: none;
 `),s(`closable`,[C(`alert-body`,[x(`title`,`
 padding-right: 24px;
 `)])]),x(`icon`,{color:`var(--n-icon-color)`}),C(`alert-body`,{padding:`var(--n-padding)`},[x(`title`,{color:`var(--n-title-text-color)`}),x(`content`,{color:`var(--n-content-text-color)`})]),N({originalTransition:`transform .3s var(--n-bezier)`,enterToProps:{transform:`scale(1)`},leaveToProps:{transform:`scale(0.9)`}}),x(`icon`,`
 position: absolute;
 left: 0;
 top: 0;
 align-items: center;
 justify-content: center;
 display: flex;
 width: var(--n-icon-size);
 height: var(--n-icon-size);
 font-size: var(--n-icon-size);
 margin: var(--n-icon-margin);
 `),x(`close`,`
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 position: absolute;
 right: 0;
 top: 0;
 margin: var(--n-close-margin);
 `),s(`show-icon`,[C(`alert-body`,{paddingLeft:`calc(var(--n-icon-margin-left) + var(--n-icon-size) + var(--n-icon-margin-right))`})]),s(`right-adjust`,[C(`alert-body`,{paddingRight:`calc(var(--n-close-size) + var(--n-padding) + 2px)`})]),C(`alert-body`,`
 border-radius: var(--n-border-radius);
 transition: border-color .3s var(--n-bezier);
 `,[x(`title`,`
 transition: color .3s var(--n-bezier);
 font-size: 16px;
 line-height: 19px;
 font-weight: var(--n-title-font-weight);
 `,[l(`& +`,[x(`content`,{marginTop:`9px`})])]),x(`content`,{transition:`color .3s var(--n-bezier)`,fontSize:`var(--n-font-size)`})]),x(`icon`,{transition:`color .3s var(--n-bezier)`})]);v(),o();var z={...t.props,title:String,showIcon:{type:Boolean,default:!0},type:{type:String,default:`default`},bordered:{type:Boolean,default:!0},closable:Boolean,onClose:Function,onAfterLeave:Function,onAfterHide:Function},B=h({name:`Alert`,inheritAttrs:!1,props:z,slots:Object,setup(n){let{mergedClsPrefixRef:r,mergedBorderedRef:o,inlineThemeDisabled:s,mergedRtlRef:c}=a(n),l=t(`Alert`,`-alert`,R,L,n,r),f=_(`Alert`,c,r),p=e(()=>{let{common:{cubicBezierEaseInOut:e},self:t}=l.value,{fontSize:r,borderRadius:i,titleFontWeight:a,lineHeight:o,iconSize:s,iconMargin:c,iconMarginRtl:f,closeIconSize:p,closeBorderRadius:m,closeSize:h,closeMargin:g,closeMarginRtl:_,padding:v}=t,{type:y}=n,{left:b,right:x}=u(c);return{"--n-bezier":e,"--n-color":t[d(`color`,y)],"--n-close-icon-size":p,"--n-close-border-radius":m,"--n-close-color-hover":t[d(`closeColorHover`,y)],"--n-close-color-pressed":t[d(`closeColorPressed`,y)],"--n-close-icon-color":t[d(`closeIconColor`,y)],"--n-close-icon-color-hover":t[d(`closeIconColorHover`,y)],"--n-close-icon-color-pressed":t[d(`closeIconColorPressed`,y)],"--n-icon-color":t[d(`iconColor`,y)],"--n-border":t[d(`border`,y)],"--n-title-text-color":t[d(`titleTextColor`,y)],"--n-content-text-color":t[d(`contentTextColor`,y)],"--n-line-height":o,"--n-border-radius":i,"--n-font-size":r,"--n-title-font-weight":a,"--n-icon-size":s,"--n-icon-margin":c,"--n-icon-margin-rtl":f,"--n-close-size":h,"--n-close-margin":g,"--n-close-margin-rtl":_,"--n-padding":v,"--n-icon-margin-left":b,"--n-icon-margin-right":x}}),m=s?O(`alert`,e(()=>n.type[0]),p,n):void 0,h=i(!0),g=()=>{let{onAfterLeave:e,onAfterHide:t}=n;e&&e(),t&&t()};return{rtlEnabled:f,mergedClsPrefix:r,mergedBordered:o,visible:h,handleCloseClick:()=>{Promise.resolve(n.onClose?.()).then(e=>{e!==!1&&(h.value=!1)})},handleAfterLeave:()=>{g()},mergedTheme:l,cssVars:s?void 0:p,themeClass:m?.themeClass,onRender:m?.onRender}},render(){return this.onRender?.(),n(),D(w,{onAfterLeave:this.handleAfterLeave},{default:()=>{let{mergedClsPrefix:e,$slots:t}=this,r={class:[`${e}-alert`,this.themeClass,this.closable&&`${e}-alert--closable`,this.showIcon&&`${e}-alert--show-icon`,!this.title&&this.closable&&`${e}-alert--right-adjust`,this.rtlEnabled&&`${e}-alert--rtl`],style:this.cssVars,role:`alert`};return this.visible?(n(),E(`div`,b({key:1},b(this.$attrs,r)),[p(()=>this.closable&&(n(),D(T,{clsPrefix:e,class:f(`${e}-alert__close`),onClick:this.handleCloseClick},null,8,[`clsPrefix`,`class`,`onClick`]))),p(()=>this.bordered&&(n(),E(`div`,{class:f(`${e}-alert__border`)},null,2))),p(()=>this.showIcon&&(n(),E(`div`,{class:f(`${e}-alert__icon`),"aria-hidden":`true`},[p(()=>y(t.icon,()=>[(n(),D(k,{clsPrefix:e},{default:()=>{switch(this.type){case`success`:return n(),D(A,{key:3});case`info`:return n(),D(M,{key:4});case`warning`:return n(),D(P,{key:5});case`error`:return n(),D(F,{key:6});default:return null}}},1032,[`clsPrefix`]))]))],2))),g(`div`,{class:f([`${e}-alert-body`,this.mergedBordered&&`${e}-alert-body--bordered`])},[p(()=>m(t.header,t=>{let r=t||this.title;return r?(n(),E(`div`,{key:2,class:f(`${e}-alert-body__title`)},[p(()=>r)],2)):null})),p(()=>t.default&&(n(),E(`div`,{class:f(`${e}-alert-body__content`)},[p(()=>t.default())],2)))],2)],16)):null}},1032,[`onAfterLeave`])}});export{B as t};