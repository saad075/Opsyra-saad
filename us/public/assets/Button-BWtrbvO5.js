import{$t as e,B as t,C as n,Cn as r,D as i,Gn as a,H as o,Ht as s,K as c,Ln as l,Mt as u,Nn as d,Nt as f,O as p,Ot as m,Pt as h,R as g,V as _,W as v,Wn as y,Y as b,Yn as x,Yt as S,_ as C,ar as w,cn as T,en as E,f as D,fn as O,gn as k,hn as A,jt as j,k as M,kt as N,m as P,n as ee,pn as F,rn as I,sn as L,t as R,tn as z,v as te,w as ne,wn as re,xn as ie,yn as B,yr as V,zt as ae}from"./is-browser-dQ-mLBB2.js";import{t as oe}from"./use-memo-Dodol12_.js";import{t as H}from"./color-to-class-G4jmrDIg.js";l(),O();function U(){let e=a(!1);return ie(()=>{e.value=!0}),y(e)}O();var W=b(`n-form-item`);function G(t,{defaultSize:n=`medium`,mergedSize:r,mergedDisabled:i}={}){let a=F(W,null);re(W,null);let o=e(r?()=>r(a):()=>{let{size:e}=t;if(e)return e;if(a){let{mergedSize:e}=a;if(e.value!==void 0)return e.value}return n}),s=e(i?()=>i(a):()=>{let{disabled:e}=t;return e===void 0?a?a.disabled.value:!1:e}),c=e(()=>{let{status:e}=t;return e||a?.mergedValidationStatus.value});return B(()=>{a&&a.restoreValidation()}),{mergedSizeRef:o,mergedDisabledRef:s,mergedStatusRef:c,nTriggerFormBlur(){a&&a.handleContentBlur()},nTriggerFormChange(){a&&a.handleContentChange()},nTriggerFormFocus(){a&&a.handleContentFocus()},nTriggerFormInput(){a&&a.handleContentInput()}}}s(),O();var K=T({name:`BaseIconSwitchTransition`,setup(e,{slots:n}){let i=U();return()=>(r(),z(ae,{name:`icon-switch-transition`,appear:i.value},t(n),1032,[`appear`]))}}),{cubicBezierEaseInOut:se}=v;function q({originalTransform:e=``,left:t=0,top:n=0,transition:r=`all .3s ${se} !important`}={}){return[m(`&.icon-switch-transition-enter-from, &.icon-switch-transition-leave-to`,{transform:`${e} scale(0.75)`,left:t,top:n,opacity:0}),m(`&.icon-switch-transition-enter-to, &.icon-switch-transition-leave-from`,{transform:`scale(1) ${e}`,left:t,top:n,opacity:1}),m(`&.icon-switch-transition-enter-active, &.icon-switch-transition-leave-active`,{transformOrigin:`center`,position:`absolute`,left:t,top:n,transition:r})]}var ce=m([m(`@keyframes rotator`,`
 0% {
 -webkit-transform: rotate(0deg);
 transform: rotate(0deg);
 }
 100% {
 -webkit-transform: rotate(360deg);
 transform: rotate(360deg);
 }`),N(`base-loading`,`
 position: relative;
 line-height: 0;
 width: 1em;
 height: 1em;
 `,[j(`transition-wrapper`,`
 position: absolute;
 width: 100%;
 height: 100%;
 `,[q()]),j(`placeholder`,`
 position: absolute;
 left: 50%;
 top: 50%;
 transform: translateX(-50%) translateY(-50%);
 `,[q({left:`50%`,top:`50%`,originalTransform:`translateX(-50%) translateY(-50%)`})]),j(`container`,`
 animation: rotator 3s linear infinite both;
 `,[j(`icon`,`
 height: 1em;
 width: 1em;
 `)])])]);O(),w(),l();var le=[`viewBox`],ue=[`values`,`dur`],de=[`stroke-width`,`cx`,`cy`,`r`,`stroke-dasharray`,`stroke-dashoffset`],fe=[`values`,`dur`],pe=[`values`,`dur`],J=`1.6s`,Y=T({name:`BaseLoading`,props:{clsPrefix:{type:String,required:!0},show:{type:Boolean,default:!0},strokeWidth:{type:Number,default:28},stroke:{type:String,default:void 0},scale:{type:Number,default:1},radius:{type:Number,default:100}},setup(e){o(`-base-loading`,ce,x(e,`clsPrefix`))},render(){let{clsPrefix:e,radius:t,strokeWidth:n,stroke:i,scale:a}=this,o=t/a;return r(),I(`div`,{class:g(`${e}-base-loading`),role:`img`,"aria-label":`loading`},[L(K,null,{default:()=>this.show?(r(),I(`div`,{key:`icon`,class:g(`${e}-base-loading__transition-wrapper`)},[E(`div`,{class:g(`${e}-base-loading__container`)},[(r(),I(`svg`,{class:g(`${e}-base-loading__icon`),viewBox:`0 0 ${2*o} ${2*o}`,xmlns:`http://www.w3.org/2000/svg`,style:V({color:i})},[E(`g`,null,[E(`animateTransform`,{attributeName:`transform`,type:`rotate`,values:`0 ${o} ${o};270 ${o} ${o}`,begin:`0s`,dur:J,fill:`freeze`,repeatCount:`indefinite`},null,8,ue),E(`circle`,{class:g(`${e}-base-loading__icon`),fill:`none`,stroke:`currentColor`,"stroke-width":n,"stroke-linecap":`round`,cx:o,cy:o,r:t-n/2,"stroke-dasharray":5.67*t,"stroke-dashoffset":18.48*t},[E(`animateTransform`,{attributeName:`transform`,type:`rotate`,values:`0 ${o} ${o};135 ${o} ${o};450 ${o} ${o}`,begin:`0s`,dur:J,fill:`freeze`,repeatCount:`indefinite`},null,8,fe),E(`animate`,{attributeName:`stroke-dashoffset`,values:`${5.67*t};${1.42*t};${5.67*t}`,begin:`0s`,dur:J,fill:`freeze`,repeatCount:`indefinite`},null,8,pe)],10,de)])],14,le))],2)],2)):(r(),I(`div`,{key:`placeholder`,class:g(`${e}-base-loading__placeholder`)},[_(()=>this.$slots.default?.())],2))},1024)],2)}}),me=R&&`chrome`in window;R&&navigator.userAgent.includes(`Firefox`);var X=R&&navigator.userAgent.includes(`Safari`)&&!me,{cubicBezierEaseInOut:Z}=v;function he({duration:e=`.2s`,delay:t=`.1s`}={}){return[m(`&.fade-in-width-expand-transition-leave-from, &.fade-in-width-expand-transition-enter-to`,{opacity:1}),m(`&.fade-in-width-expand-transition-leave-to, &.fade-in-width-expand-transition-enter-from`,`
 opacity: 0!important;
 margin-left: 0!important;
 margin-right: 0!important;
 `),m(`&.fade-in-width-expand-transition-leave-active`,`
 overflow: hidden;
 transition:
 opacity ${e} ${Z},
 max-width ${e} ${Z} ${t},
 margin-left ${e} ${Z} ${t},
 margin-right ${e} ${Z} ${t};
 `),m(`&.fade-in-width-expand-transition-enter-active`,`
 overflow: hidden;
 transition:
 opacity ${e} ${Z} ${t},
 max-width ${e} ${Z},
 margin-left ${e} ${Z},
 margin-right ${e} ${Z};
 `)]}var ge=N(`base-wave`,`
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 border-radius: inherit;
`);O(),l();var _e=T({name:`BaseWave`,props:{clsPrefix:{type:String,required:!0}},setup(e){o(`-base-wave`,ge,x(e,`clsPrefix`));let t=a(null),n=a(!1),r=null;return B(()=>{r!==null&&window.clearTimeout(r)}),{active:n,selfRef:t,play(){r!==null&&(window.clearTimeout(r),n.value=!1,r=null),k(()=>{t.value?.offsetHeight,n.value=!0,r=window.setTimeout(()=>{n.value=!1,r=null},1e3)})}}},render(){let{clsPrefix:e}=this;return r(),I(`div`,{ref:`selfRef`,"aria-hidden":!0,class:g([`${e}-base-wave`,this.active&&`${e}-base-wave--active`])},null,2)}}),ve={paddingTiny:`0 6px`,paddingSmall:`0 10px`,paddingMedium:`0 14px`,paddingLarge:`0 18px`,paddingRoundTiny:`0 10px`,paddingRoundSmall:`0 14px`,paddingRoundMedium:`0 18px`,paddingRoundLarge:`0 22px`,iconMarginTiny:`6px`,iconMarginSmall:`6px`,iconMarginMedium:`6px`,iconMarginLarge:`6px`,iconSizeTiny:`14px`,iconSizeSmall:`18px`,iconSizeMedium:`18px`,iconSizeLarge:`20px`,rippleDuration:`.6s`};function ye(e){let{heightTiny:t,heightSmall:n,heightMedium:r,heightLarge:i,borderRadius:a,fontSizeTiny:o,fontSizeSmall:s,fontSizeMedium:c,fontSizeLarge:l,opacityDisabled:u,textColor2:d,textColor3:f,primaryColorHover:p,primaryColorPressed:m,borderColor:h,primaryColor:g,baseColor:_,infoColor:v,infoColorHover:y,infoColorPressed:b,successColor:x,successColorHover:S,successColorPressed:C,warningColor:w,warningColorHover:T,warningColorPressed:E,errorColor:D,errorColorHover:O,errorColorPressed:k,fontWeight:A,buttonColor2:j,buttonColor2Hover:M,buttonColor2Pressed:N,fontWeightStrong:P}=e;return{...ve,heightTiny:t,heightSmall:n,heightMedium:r,heightLarge:i,borderRadiusTiny:a,borderRadiusSmall:a,borderRadiusMedium:a,borderRadiusLarge:a,fontSizeTiny:o,fontSizeSmall:s,fontSizeMedium:c,fontSizeLarge:l,opacityDisabled:u,colorOpacitySecondary:`0.16`,colorOpacitySecondaryHover:`0.22`,colorOpacitySecondaryPressed:`0.28`,colorSecondary:j,colorSecondaryHover:M,colorSecondaryPressed:N,colorTertiary:j,colorTertiaryHover:M,colorTertiaryPressed:N,colorQuaternary:`#0000`,colorQuaternaryHover:M,colorQuaternaryPressed:N,color:`#0000`,colorHover:`#0000`,colorPressed:`#0000`,colorFocus:`#0000`,colorDisabled:`#0000`,textColor:d,textColorTertiary:f,textColorHover:p,textColorPressed:m,textColorFocus:p,textColorDisabled:d,textColorText:d,textColorTextHover:p,textColorTextPressed:m,textColorTextFocus:p,textColorTextDisabled:d,textColorGhost:d,textColorGhostHover:p,textColorGhostPressed:m,textColorGhostFocus:p,textColorGhostDisabled:d,border:`1px solid ${h}`,borderHover:`1px solid ${p}`,borderPressed:`1px solid ${m}`,borderFocus:`1px solid ${p}`,borderDisabled:`1px solid ${h}`,rippleColor:g,colorPrimary:g,colorHoverPrimary:p,colorPressedPrimary:m,colorFocusPrimary:p,colorDisabledPrimary:g,textColorPrimary:_,textColorHoverPrimary:_,textColorPressedPrimary:_,textColorFocusPrimary:_,textColorDisabledPrimary:_,textColorTextPrimary:g,textColorTextHoverPrimary:p,textColorTextPressedPrimary:m,textColorTextFocusPrimary:p,textColorTextDisabledPrimary:d,textColorGhostPrimary:g,textColorGhostHoverPrimary:p,textColorGhostPressedPrimary:m,textColorGhostFocusPrimary:p,textColorGhostDisabledPrimary:g,borderPrimary:`1px solid ${g}`,borderHoverPrimary:`1px solid ${p}`,borderPressedPrimary:`1px solid ${m}`,borderFocusPrimary:`1px solid ${p}`,borderDisabledPrimary:`1px solid ${g}`,rippleColorPrimary:g,colorInfo:v,colorHoverInfo:y,colorPressedInfo:b,colorFocusInfo:y,colorDisabledInfo:v,textColorInfo:_,textColorHoverInfo:_,textColorPressedInfo:_,textColorFocusInfo:_,textColorDisabledInfo:_,textColorTextInfo:v,textColorTextHoverInfo:y,textColorTextPressedInfo:b,textColorTextFocusInfo:y,textColorTextDisabledInfo:d,textColorGhostInfo:v,textColorGhostHoverInfo:y,textColorGhostPressedInfo:b,textColorGhostFocusInfo:y,textColorGhostDisabledInfo:v,borderInfo:`1px solid ${v}`,borderHoverInfo:`1px solid ${y}`,borderPressedInfo:`1px solid ${b}`,borderFocusInfo:`1px solid ${y}`,borderDisabledInfo:`1px solid ${v}`,rippleColorInfo:v,colorSuccess:x,colorHoverSuccess:S,colorPressedSuccess:C,colorFocusSuccess:S,colorDisabledSuccess:x,textColorSuccess:_,textColorHoverSuccess:_,textColorPressedSuccess:_,textColorFocusSuccess:_,textColorDisabledSuccess:_,textColorTextSuccess:x,textColorTextHoverSuccess:S,textColorTextPressedSuccess:C,textColorTextFocusSuccess:S,textColorTextDisabledSuccess:d,textColorGhostSuccess:x,textColorGhostHoverSuccess:S,textColorGhostPressedSuccess:C,textColorGhostFocusSuccess:S,textColorGhostDisabledSuccess:x,borderSuccess:`1px solid ${x}`,borderHoverSuccess:`1px solid ${S}`,borderPressedSuccess:`1px solid ${C}`,borderFocusSuccess:`1px solid ${S}`,borderDisabledSuccess:`1px solid ${x}`,rippleColorSuccess:x,colorWarning:w,colorHoverWarning:T,colorPressedWarning:E,colorFocusWarning:T,colorDisabledWarning:w,textColorWarning:_,textColorHoverWarning:_,textColorPressedWarning:_,textColorFocusWarning:_,textColorDisabledWarning:_,textColorTextWarning:w,textColorTextHoverWarning:T,textColorTextPressedWarning:E,textColorTextFocusWarning:T,textColorTextDisabledWarning:d,textColorGhostWarning:w,textColorGhostHoverWarning:T,textColorGhostPressedWarning:E,textColorGhostFocusWarning:T,textColorGhostDisabledWarning:w,borderWarning:`1px solid ${w}`,borderHoverWarning:`1px solid ${T}`,borderPressedWarning:`1px solid ${E}`,borderFocusWarning:`1px solid ${T}`,borderDisabledWarning:`1px solid ${w}`,rippleColorWarning:w,colorError:D,colorHoverError:O,colorPressedError:k,colorFocusError:O,colorDisabledError:D,textColorError:_,textColorHoverError:_,textColorPressedError:_,textColorFocusError:_,textColorDisabledError:_,textColorTextError:D,textColorTextHoverError:O,textColorTextPressedError:k,textColorTextFocusError:O,textColorTextDisabledError:d,textColorGhostError:D,textColorGhostHoverError:O,textColorGhostPressedError:k,textColorGhostFocusError:O,textColorGhostDisabledError:D,borderError:`1px solid ${D}`,borderHoverError:`1px solid ${O}`,borderPressedError:`1px solid ${k}`,borderFocusError:`1px solid ${O}`,borderDisabledError:`1px solid ${D}`,rippleColorError:D,waveOpacity:`0.6`,fontWeight:A,fontWeightStrong:P}}var be={name:`Button`,common:i,self:ye};function Q(e){return M(e,[255,255,255,.16])}function $(e){return M(e,[0,0,0,.12])}var xe=b(`n-button-group`),Se=m([N(`button`,`
 margin: 0;
 font-weight: var(--n-font-weight);
 line-height: 1;
 font-family: inherit;
 padding: var(--n-padding);
 height: var(--n-height);
 font-size: var(--n-font-size);
 border-radius: var(--n-border-radius);
 color: var(--n-text-color);
 background-color: var(--n-color);
 width: var(--n-width);
 white-space: nowrap;
 outline: none;
 position: relative;
 z-index: auto;
 border: none;
 display: inline-flex;
 flex-wrap: nowrap;
 flex-shrink: 0;
 align-items: center;
 justify-content: center;
 user-select: none;
 -webkit-user-select: none;
 text-align: center;
 cursor: pointer;
 text-decoration: none;
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 opacity .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[u(`color`,[j(`border`,{borderColor:`var(--n-border-color)`}),u(`disabled`,[j(`border`,{borderColor:`var(--n-border-color-disabled)`})]),f(`disabled`,[m(`&:focus`,[j(`state-border`,{borderColor:`var(--n-border-color-focus)`})]),m(`&:hover`,[j(`state-border`,{borderColor:`var(--n-border-color-hover)`})]),m(`&:active`,[j(`state-border`,{borderColor:`var(--n-border-color-pressed)`})]),u(`pressed`,[j(`state-border`,{borderColor:`var(--n-border-color-pressed)`})])])]),u(`disabled`,{backgroundColor:`var(--n-color-disabled)`,color:`var(--n-text-color-disabled)`},[j(`border`,{border:`var(--n-border-disabled)`})]),f(`disabled`,[m(`&:focus`,{backgroundColor:`var(--n-color-focus)`,color:`var(--n-text-color-focus)`},[j(`state-border`,{border:`var(--n-border-focus)`})]),m(`&:hover`,{backgroundColor:`var(--n-color-hover)`,color:`var(--n-text-color-hover)`},[j(`state-border`,{border:`var(--n-border-hover)`})]),m(`&:active`,{backgroundColor:`var(--n-color-pressed)`,color:`var(--n-text-color-pressed)`},[j(`state-border`,{border:`var(--n-border-pressed)`})]),u(`pressed`,{backgroundColor:`var(--n-color-pressed)`,color:`var(--n-text-color-pressed)`},[j(`state-border`,{border:`var(--n-border-pressed)`})])]),u(`loading`,`cursor: wait;`),N(`base-wave`,`
 pointer-events: none;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 animation-iteration-count: 1;
 animation-duration: var(--n-ripple-duration);
 animation-timing-function: var(--n-bezier-ease-out), var(--n-bezier-ease-out);
 `,[u(`active`,{zIndex:1,animationName:`button-wave-spread, button-wave-opacity`})]),R&&`MozBoxSizing`in document.createElement(`div`).style?m(`&::moz-focus-inner`,{border:0}):null,j(`border, state-border`,`
 position: absolute;
 left: 0;
 top: 0;
 right: 0;
 bottom: 0;
 border-radius: inherit;
 transition: border-color .3s var(--n-bezier);
 pointer-events: none;
 `),j(`border`,`
 border: var(--n-border);
 `),j(`state-border`,`
 border: var(--n-border);
 border-color: #0000;
 z-index: 1;
 `),j(`icon`,`
 margin: var(--n-icon-margin);
 margin-left: 0;
 height: var(--n-icon-size);
 width: var(--n-icon-size);
 max-width: var(--n-icon-size);
 font-size: var(--n-icon-size);
 position: relative;
 flex-shrink: 0;
 `,[N(`icon-slot`,`
 height: var(--n-icon-size);
 width: var(--n-icon-size);
 position: absolute;
 left: 0;
 top: 50%;
 transform: translateY(-50%);
 display: flex;
 align-items: center;
 justify-content: center;
 `,[q({top:`50%`,originalTransform:`translateY(-50%)`})]),he()]),j(`content`,`
 display: flex;
 align-items: center;
 flex-wrap: nowrap;
 min-width: 0;
 `,[m(`~`,[j(`icon`,{margin:`var(--n-icon-margin)`,marginRight:0})])]),u(`block`,`
 display: flex;
 width: 100%;
 `),u(`dashed`,[j(`border, state-border`,{borderStyle:`dashed !important`})]),u(`disabled`,{cursor:`not-allowed`,opacity:`var(--n-opacity-disabled)`})]),m(`@keyframes button-wave-spread`,{from:{boxShadow:`0 0 0.5px 0 var(--n-ripple-color)`},to:{boxShadow:`0 0 0.5px 4.5px var(--n-ripple-color)`}}),m(`@keyframes button-wave-opacity`,{from:{opacity:`var(--n-wave-opacity)`},to:{opacity:0}})]);O(),w(),l();var Ce={...n.props,color:String,textColor:String,text:Boolean,block:Boolean,loading:Boolean,disabled:Boolean,circle:Boolean,size:String,ghost:Boolean,round:Boolean,secondary:Boolean,tertiary:Boolean,quaternary:Boolean,strong:Boolean,focusable:{type:Boolean,default:!0},keyboard:{type:Boolean,default:!0},tag:{type:String,default:`button`},type:{type:String,default:`default`},dashed:Boolean,renderIcon:Function,iconPlacement:{type:String,default:`left`},attrType:{type:String,default:`button`},bordered:{type:Boolean,default:!0},onClick:[Function,Array],nativeFocusBehavior:{type:Boolean,default:!X},spinProps:Object},we=T({name:`Button`,props:Ce,slots:Object,setup(t){let r=a(null),i=a(null),o=a(!1),s=oe(()=>!t.quaternary&&!t.tertiary&&!t.secondary&&!t.text&&(!t.color||t.ghost||t.dashed)&&t.bordered),l=F(xe,{}),{inlineThemeDisabled:u,mergedClsPrefixRef:d,mergedRtlRef:f,mergedComponentPropsRef:m}=c(t),{mergedSizeRef:g}=G({},{defaultSize:`medium`,mergedSize:e=>{let{size:n}=t;if(n)return n;let{size:r}=l;if(r)return r;let{mergedSize:i}=e||{};return i?i.value:m?.value?.Button?.size||`medium`}}),_=e(()=>t.focusable&&!t.disabled),v=e=>{_.value||e.preventDefault(),!t.nativeFocusBehavior&&(e.preventDefault(),!t.disabled&&_.value&&r.value?.focus({preventScroll:!0}))},y=e=>{if(!t.disabled&&!t.loading){let{onClick:n}=t;n&&te(n,e),t.text||i.value?.play()}},b=e=>{if(e.key===`Enter`){if(!t.keyboard)return;o.value=!1}},x=e=>{if(e.key===`Enter`){if(!t.keyboard||t.loading){e.preventDefault();return}o.value=!0}},S=()=>{o.value=!1},C=n(`Button`,`-button`,Se,be,t,d),w=D(`Button`,f,d),T=e(()=>{let{common:{cubicBezierEaseInOut:e,cubicBezierEaseOut:n},self:r}=C.value,{rippleDuration:i,opacityDisabled:a,fontWeight:o,fontWeightStrong:s}=r,c=g.value,{dashed:l,type:u,ghost:d,text:f,color:m,round:_,circle:v,textColor:y,secondary:b,tertiary:x,quaternary:S,strong:w}=t,T={"--n-font-weight":w?s:o},E={"--n-color":`initial`,"--n-color-hover":`initial`,"--n-color-pressed":`initial`,"--n-color-focus":`initial`,"--n-color-disabled":`initial`,"--n-ripple-color":`initial`,"--n-text-color":`initial`,"--n-text-color-hover":`initial`,"--n-text-color-pressed":`initial`,"--n-text-color-focus":`initial`,"--n-text-color-disabled":`initial`},D=u===`tertiary`,O=u==="default",k=D?`default`:u;if(f){let e=y||m;E={"--n-color":`#0000`,"--n-color-hover":`#0000`,"--n-color-pressed":`#0000`,"--n-color-focus":`#0000`,"--n-color-disabled":`#0000`,"--n-ripple-color":`#0000`,"--n-text-color":e||r[h(`textColorText`,k)],"--n-text-color-hover":e?Q(e):r[h(`textColorTextHover`,k)],"--n-text-color-pressed":e?$(e):r[h(`textColorTextPressed`,k)],"--n-text-color-focus":e?Q(e):r[h(`textColorTextHover`,k)],"--n-text-color-disabled":e||r[h(`textColorTextDisabled`,k)]}}else if(d||l){let e=y||m;E={"--n-color":`#0000`,"--n-color-hover":`#0000`,"--n-color-pressed":`#0000`,"--n-color-focus":`#0000`,"--n-color-disabled":`#0000`,"--n-ripple-color":m||r[h(`rippleColor`,k)],"--n-text-color":e||r[h(`textColorGhost`,k)],"--n-text-color-hover":e?Q(e):r[h(`textColorGhostHover`,k)],"--n-text-color-pressed":e?$(e):r[h(`textColorGhostPressed`,k)],"--n-text-color-focus":e?Q(e):r[h(`textColorGhostHover`,k)],"--n-text-color-disabled":e||r[h(`textColorGhostDisabled`,k)]}}else if(b){let e=O?r.textColor:D?r.textColorTertiary:r[h(`color`,k)],t=m||e,n=u!=="default"&&u!==`tertiary`;E={"--n-color":n?p(t,{alpha:Number(r.colorOpacitySecondary)}):r.colorSecondary,"--n-color-hover":n?p(t,{alpha:Number(r.colorOpacitySecondaryHover)}):r.colorSecondaryHover,"--n-color-pressed":n?p(t,{alpha:Number(r.colorOpacitySecondaryPressed)}):r.colorSecondaryPressed,"--n-color-focus":n?p(t,{alpha:Number(r.colorOpacitySecondaryHover)}):r.colorSecondaryHover,"--n-color-disabled":r.colorSecondary,"--n-ripple-color":`#0000`,"--n-text-color":t,"--n-text-color-hover":t,"--n-text-color-pressed":t,"--n-text-color-focus":t,"--n-text-color-disabled":t}}else if(x||S){let e=O?r.textColor:D?r.textColorTertiary:r[h(`color`,k)],t=m||e;x?(E[`--n-color`]=r.colorTertiary,E[`--n-color-hover`]=r.colorTertiaryHover,E[`--n-color-pressed`]=r.colorTertiaryPressed,E[`--n-color-focus`]=r.colorSecondaryHover,E[`--n-color-disabled`]=r.colorTertiary):(E[`--n-color`]=r.colorQuaternary,E[`--n-color-hover`]=r.colorQuaternaryHover,E[`--n-color-pressed`]=r.colorQuaternaryPressed,E[`--n-color-focus`]=r.colorQuaternaryHover,E[`--n-color-disabled`]=r.colorQuaternary),E[`--n-ripple-color`]=`#0000`,E[`--n-text-color`]=t,E[`--n-text-color-hover`]=t,E[`--n-text-color-pressed`]=t,E[`--n-text-color-focus`]=t,E[`--n-text-color-disabled`]=t}else E={"--n-color":m||r[h(`color`,k)],"--n-color-hover":m?Q(m):r[h(`colorHover`,k)],"--n-color-pressed":m?$(m):r[h(`colorPressed`,k)],"--n-color-focus":m?Q(m):r[h(`colorFocus`,k)],"--n-color-disabled":m||r[h(`colorDisabled`,k)],"--n-ripple-color":m||r[h(`rippleColor`,k)],"--n-text-color":y||(m?r.textColorPrimary:D?r.textColorTertiary:r[h(`textColor`,k)]),"--n-text-color-hover":y||(m?r.textColorHoverPrimary:r[h(`textColorHover`,k)]),"--n-text-color-pressed":y||(m?r.textColorPressedPrimary:r[h(`textColorPressed`,k)]),"--n-text-color-focus":y||(m?r.textColorFocusPrimary:r[h(`textColorFocus`,k)]),"--n-text-color-disabled":y||(m?r.textColorDisabledPrimary:r[h(`textColorDisabled`,k)])};let A={"--n-border":`initial`,"--n-border-hover":`initial`,"--n-border-pressed":`initial`,"--n-border-focus":`initial`,"--n-border-disabled":`initial`};A=f?{"--n-border":`none`,"--n-border-hover":`none`,"--n-border-pressed":`none`,"--n-border-focus":`none`,"--n-border-disabled":`none`}:{"--n-border":r[h(`border`,k)],"--n-border-hover":r[h(`borderHover`,k)],"--n-border-pressed":r[h(`borderPressed`,k)],"--n-border-focus":r[h(`borderFocus`,k)],"--n-border-disabled":r[h(`borderDisabled`,k)]};let{[h(`height`,c)]:j,[h(`fontSize`,c)]:M,[h(`padding`,c)]:N,[h(`paddingRound`,c)]:P,[h(`iconSize`,c)]:ee,[h(`borderRadius`,c)]:F,[h(`iconMargin`,c)]:I,waveOpacity:L}=r,R={"--n-width":v&&!f?j:`initial`,"--n-height":f?`initial`:j,"--n-font-size":M,"--n-padding":v||f?`initial`:_?P:N,"--n-icon-size":ee,"--n-icon-margin":I,"--n-border-radius":f?`initial`:v||_?j:F};return{"--n-bezier":e,"--n-bezier-ease-out":n,"--n-ripple-duration":i,"--n-opacity-disabled":a,"--n-wave-opacity":L,...T,...E,...A,...R}}),E=u?ne(`button`,e(()=>{let e=``,{dashed:n,type:r,ghost:i,text:a,color:o,round:s,circle:c,textColor:l,secondary:u,tertiary:d,quaternary:f,strong:p}=t;n&&(e+=`a`),i&&(e+=`b`),a&&(e+=`c`),s&&(e+=`d`),c&&(e+=`e`),u&&(e+=`f`),d&&(e+=`g`),f&&(e+=`h`),p&&(e+=`i`),o&&(e+=`j${H(o)}`),l&&(e+=`k${H(l)}`);let{value:m}=g;return e+=`l${m[0]}`,e+=`m${r[0]}`,e}),T,t):void 0;return{selfElRef:r,waveElRef:i,mergedClsPrefix:d,mergedFocusable:_,mergedSize:g,showBorder:s,enterPressed:o,rtlEnabled:w,handleMousedown:v,handleKeydown:x,handleBlur:S,handleKeyup:b,handleClick:y,customColorCssVars:e(()=>{let{color:e}=t;if(!e)return null;let n=Q(e);return{"--n-border-color":e,"--n-border-color-hover":n,"--n-border-color-pressed":$(e),"--n-border-color-focus":n,"--n-border-color-disabled":e}}),cssVars:u?void 0:T,themeClass:E?.themeClass,onRender:E?.onRender}},render(){let{mergedClsPrefix:e,tag:t,onRender:n}=this;n?.();let i=C(this.$slots.default,t=>t&&(r(),I(`span`,{class:g(`${e}-button__content`)},[_(()=>t)],2)));return r(),z(t,{ref:`selfElRef`,class:g([this.themeClass,`${e}-button`,`${e}-button--${this.type}-type`,`${e}-button--${this.mergedSize}-type`,this.rtlEnabled&&`${e}-button--rtl`,this.disabled&&`${e}-button--disabled`,this.block&&`${e}-button--block`,this.enterPressed&&`${e}-button--pressed`,!this.text&&this.dashed&&`${e}-button--dashed`,this.color&&`${e}-button--color`,this.secondary&&`${e}-button--secondary`,this.loading&&`${e}-button--loading`,this.ghost&&`${e}-button--ghost`]),tabindex:this.mergedFocusable?0:-1,type:this.attrType,style:V(this.cssVars),disabled:this.disabled,onClick:this.handleClick,onBlur:this.handleBlur,onMousedown:this.handleMousedown,onKeyup:this.handleKeyup,onKeydown:this.handleKeydown},{default:d(()=>[_(()=>this.iconPlacement===`right`&&i),L(ee,{width:!0},{default:()=>C(this.$slots.icon,t=>(this.loading||this.renderIcon||t)&&(r(),I(`span`,{class:g(`${e}-button__icon`),style:V({margin:P(this.$slots.default)?`0`:``})},[L(K,null,{default:()=>this.loading?(r(),z(Y,A({clsPrefix:e,key:`loading`,class:`${e}-icon-slot`,strokeWidth:20},this.spinProps),null,16,[`clsPrefix`,`class`])):(r(),I(`div`,{key:`icon`,class:g(`${e}-icon-slot`),role:`none`},[this.renderIcon?(r(),I(S,{key:0},[_(()=>this.renderIcon())],64)):(r(),I(S,{key:1},[_(()=>t)],64))],2))},1024)],6)))},1024),_(()=>this.iconPlacement===`left`&&i),this.text?_(()=>null):(r(),z(_e,{key:0,ref:`waveElRef`,clsPrefix:e},null,8,[`clsPrefix`])),this.showBorder?(r(),I(`div`,{key:2,"aria-hidden":!0,class:g(`${e}-button__border`),style:V(this.customColorCssVars)},null,6)):_(()=>null),this.showBorder?(r(),I(`div`,{key:4,"aria-hidden":!0,class:g(`${e}-button__state-border`),style:V(this.customColorCssVars)},null,6)):_(()=>null)]),_:2},1032,[`class`,`tabindex`,`type`,`style`,`disabled`,`onClick`,`onBlur`,`onMousedown`,`onKeyup`,`onKeydown`])}}),Te=we;export{ye as a,q as c,G as d,U as f,be as i,K as l,Te as n,X as o,xe as r,Y as s,we as t,W as u};