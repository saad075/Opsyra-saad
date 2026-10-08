import{$t as e,C as t,Cn as n,D as r,F as i,Gn as a,K as o,Ln as s,M as c,Mt as l,Nt as u,O as d,Ot as f,Pt as p,R as m,V as h,Yn as g,_,ar as v,cn as y,en as b,fn as x,hn as S,jt as C,kt as w,m as T,rn as E,tn as D,v as O,w as k,yr as A}from"./is-browser-dQ-mLBB2.js";import{t as j}from"./use-merged-state-BNqaTJd4.js";import{c as M,d as N,l as P,s as F}from"./Button-BWtrbvO5.js";import{u as I}from"./index-Cc8HQXPT.js";function L(e){let{primaryColor:t,opacityDisabled:n,borderRadius:r,textColor3:i}=e;return{...I,iconColor:i,textColor:`white`,loadingColor:t,opacityDisabled:n,railColor:`rgba(0, 0, 0, .14)`,railColorActive:t,buttonBoxShadow:`0 1px 4px 0 rgba(0, 0, 0, 0.3), inset 0 0 1px 0 rgba(0, 0, 0, 0.05)`,buttonColor:`#FFF`,railBorderRadiusSmall:r,railBorderRadiusMedium:r,railBorderRadiusLarge:r,buttonBorderRadiusSmall:r,buttonBorderRadiusMedium:r,buttonBorderRadiusLarge:r,boxShadowFocus:`0 0 0 2px ${d(t,{alpha:.2})}`}}var R={name:`Switch`,common:r,self:L},z=w(`switch`,`
 height: var(--n-height);
 min-width: var(--n-width);
 vertical-align: middle;
 user-select: none;
 -webkit-user-select: none;
 display: inline-flex;
 outline: none;
 justify-content: center;
 align-items: center;
`,[C(`children-placeholder`,`
 height: var(--n-rail-height);
 display: flex;
 flex-direction: column;
 overflow: hidden;
 pointer-events: none;
 visibility: hidden;
 `),C(`rail-placeholder`,`
 display: flex;
 flex-wrap: none;
 `),C(`button-placeholder`,`
 width: calc(1.75 * var(--n-rail-height));
 height: var(--n-rail-height);
 `),w(`base-loading`,`
 position: absolute;
 top: 50%;
 left: 50%;
 transform: translateX(-50%) translateY(-50%);
 font-size: calc(var(--n-button-width) - 4px);
 color: var(--n-loading-color);
 transition: color .3s var(--n-bezier);
 `,[M({left:`50%`,top:`50%`,originalTransform:`translateX(-50%) translateY(-50%)`})]),C(`checked, unchecked`,`
 transition: color .3s var(--n-bezier);
 color: var(--n-text-color);
 box-sizing: border-box;
 position: absolute;
 white-space: nowrap;
 top: 0;
 bottom: 0;
 display: flex;
 align-items: center;
 line-height: 1;
 `),C(`checked`,`
 right: 0;
 padding-right: calc(1.25 * var(--n-rail-height) - var(--n-offset));
 `),C(`unchecked`,`
 left: 0;
 justify-content: flex-end;
 padding-left: calc(1.25 * var(--n-rail-height) - var(--n-offset));
 `),f(`&:focus`,[C(`rail`,`
 box-shadow: var(--n-box-shadow-focus);
 `)]),l(`round`,[C(`rail`,`border-radius: calc(var(--n-rail-height) / 2);`,[C(`button`,`border-radius: calc(var(--n-button-height) / 2);`)])]),u(`disabled`,[u(`icon`,[l(`rubber-band`,[l(`pressed`,[C(`rail`,[C(`button`,`max-width: var(--n-button-width-pressed);`)])]),C(`rail`,[f(`&:active`,[C(`button`,`max-width: var(--n-button-width-pressed);`)])]),l(`active`,[l(`pressed`,[C(`rail`,[C(`button`,`left: calc(100% - var(--n-offset) - var(--n-button-width-pressed));`)])]),C(`rail`,[f(`&:active`,[C(`button`,`left: calc(100% - var(--n-offset) - var(--n-button-width-pressed));`)])])])])])]),l(`active`,[C(`rail`,[C(`button`,`left: calc(100% - var(--n-button-width) - var(--n-offset))`)])]),C(`rail`,`
 overflow: hidden;
 height: var(--n-rail-height);
 min-width: var(--n-rail-width);
 border-radius: var(--n-rail-border-radius);
 cursor: pointer;
 position: relative;
 transition:
 opacity .3s var(--n-bezier),
 background .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 background-color: var(--n-rail-color);
 `,[C(`button-icon`,`
 color: var(--n-icon-color);
 transition: color .3s var(--n-bezier);
 font-size: calc(var(--n-button-height) - 4px);
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 display: flex;
 justify-content: center;
 align-items: center;
 line-height: 1;
 `,[M()]),C(`button`,`
 align-items: center; 
 top: var(--n-offset);
 left: var(--n-offset);
 height: var(--n-button-height);
 width: var(--n-button-width-pressed);
 max-width: var(--n-button-width);
 border-radius: var(--n-button-border-radius);
 background-color: var(--n-button-color);
 box-shadow: var(--n-button-box-shadow);
 box-sizing: border-box;
 cursor: inherit;
 content: "";
 position: absolute;
 transition:
 background-color .3s var(--n-bezier),
 left .3s var(--n-bezier),
 opacity .3s var(--n-bezier),
 max-width .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 `)]),l(`active`,[C(`rail`,`background-color: var(--n-rail-color-active);`)]),l(`loading`,[C(`rail`,`
 cursor: wait;
 `)]),l(`disabled`,[C(`rail`,`
 cursor: not-allowed;
 opacity: .5;
 `)])]);x(),v(),s();var B=[`aria-checked`,`tabindex`,`onClick`,`onFocus`,`onBlur`,`onKeyup`,`onKeydown`],V={...t.props,size:String,value:{type:[String,Number,Boolean],default:void 0},loading:Boolean,defaultValue:{type:[String,Number,Boolean],default:!1},disabled:{type:Boolean,default:void 0},round:{type:Boolean,default:!0},"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array],checkedValue:{type:[String,Number,Boolean],default:!0},uncheckedValue:{type:[String,Number,Boolean],default:!1},railStyle:Function,rubberBand:{type:Boolean,default:!0},spinProps:Object,onChange:[Function,Array]},H,U=y({name:`Switch`,props:V,slots:Object,setup(n){H===void 0&&(H=typeof CSS<`u`?CSS.supports!==void 0&&CSS.supports(`width`,`max(1px)`):!0);let{mergedClsPrefixRef:r,inlineThemeDisabled:s,mergedComponentPropsRef:l}=o(n),u=t(`Switch`,`-switch`,z,R,n,r),d=N(n,{mergedSize(e){return n.size===void 0?e?e.mergedSize.value:l?.value?.Switch?.size||`medium`:n.size}}),{mergedSizeRef:f,mergedDisabledRef:m}=d,h=a(n.defaultValue),_=g(n,`value`),v=j(_,h),y=e(()=>v.value===n.checkedValue),b=a(!1),x=a(!1),S=e(()=>{let{railStyle:e}=n;if(e)return e({focused:x.value,checked:y.value})});function C(e){let{"onUpdate:value":t,onChange:r,onUpdateValue:i}=n,{nTriggerFormInput:a,nTriggerFormChange:o}=d;t&&O(t,e),i&&O(i,e),r&&O(r,e),h.value=e,a(),o()}function w(){let{nTriggerFormFocus:e}=d;e()}function T(){let{nTriggerFormBlur:e}=d;e()}function E(){n.loading||m.value||(v.value===n.checkedValue?C(n.uncheckedValue):C(n.checkedValue))}function D(){x.value=!0,w()}function A(){x.value=!1,T(),b.value=!1}function M(e){n.loading||m.value||e.key===` `&&(v.value===n.checkedValue?C(n.uncheckedValue):C(n.checkedValue),b.value=!1)}function P(e){n.loading||m.value||e.key===` `&&(e.preventDefault(),b.value=!0)}let F=e(()=>{let{value:e}=f,{self:{opacityDisabled:t,railColor:n,railColorActive:r,buttonBoxShadow:a,buttonColor:o,boxShadowFocus:s,loadingColor:l,textColor:d,iconColor:m,[p(`buttonHeight`,e)]:h,[p(`buttonWidth`,e)]:g,[p(`buttonWidthPressed`,e)]:_,[p(`railHeight`,e)]:v,[p(`railWidth`,e)]:y,[p(`railBorderRadius`,e)]:b,[p(`buttonBorderRadius`,e)]:x},common:{cubicBezierEaseInOut:S}}=u.value,C,w,T;return H?(C=`calc((${v} - ${h}) / 2)`,w=`max(${v}, ${h})`,T=`max(${y}, calc(${y} + ${h} - ${v}))`):(C=i((c(v)-c(h))/2),w=i(Math.max(c(v),c(h))),T=c(v)>c(h)?y:i(c(y)+c(h)-c(v))),{"--n-bezier":S,"--n-button-border-radius":x,"--n-button-box-shadow":a,"--n-button-color":o,"--n-button-width":g,"--n-button-width-pressed":_,"--n-button-height":h,"--n-height":w,"--n-offset":C,"--n-opacity-disabled":t,"--n-rail-border-radius":b,"--n-rail-color":n,"--n-rail-color-active":r,"--n-rail-height":v,"--n-rail-width":y,"--n-width":T,"--n-box-shadow-focus":s,"--n-loading-color":l,"--n-text-color":d,"--n-icon-color":m}}),I=s?k(`switch`,e(()=>f.value[0]),F,n):void 0;return{handleClick:E,handleBlur:A,handleFocus:D,handleKeyup:M,handleKeydown:P,mergedRailStyle:S,pressed:b,mergedClsPrefix:r,mergedValue:v,checked:y,mergedDisabled:m,cssVars:s?void 0:F,themeClass:I?.themeClass,onRender:I?.onRender}},render(){let{mergedClsPrefix:e,mergedDisabled:t,checked:r,mergedRailStyle:i,onRender:a,$slots:o}=this;a?.();let{checked:s,unchecked:c,icon:l,"checked-icon":u,"unchecked-icon":d}=o,f=!(T(l)&&T(u)&&T(d));return n(),E(`div`,{role:`switch`,"aria-checked":r,class:m([`${e}-switch`,this.themeClass,f&&`${e}-switch--icon`,r&&`${e}-switch--active`,t&&`${e}-switch--disabled`,this.round&&`${e}-switch--round`,this.loading&&`${e}-switch--loading`,this.pressed&&`${e}-switch--pressed`,this.rubberBand&&`${e}-switch--rubber-band`]),tabindex:this.mergedDisabled?void 0:0,style:A(this.cssVars),onClick:this.handleClick,onFocus:this.handleFocus,onBlur:this.handleBlur,onKeyup:this.handleKeyup,onKeydown:this.handleKeydown},[b(`div`,{class:m(`${e}-switch__rail`),"aria-hidden":`true`,style:A(i)},[h(()=>_(s,t=>_(c,r=>t||r?(n(),E(`div`,{key:4,"aria-hidden":!0,class:m(`${e}-switch__children-placeholder`)},[b(`div`,{class:m(`${e}-switch__rail-placeholder`)},[b(`div`,{class:m(`${e}-switch__button-placeholder`)},null,2),h(()=>t)],2),b(`div`,{class:m(`${e}-switch__rail-placeholder`)},[b(`div`,{class:m(`${e}-switch__button-placeholder`)},null,2),h(()=>r)],2)],2)):null))),b(`div`,{class:m(`${e}-switch__button`)},[h(()=>_(l,t=>_(u,r=>_(d,i=>(n(),D(P,null,{default:()=>this.loading?(n(),D(F,S({key:`loading`,clsPrefix:e,strokeWidth:20},this.spinProps),null,16,[`clsPrefix`])):this.checked&&(r||t)?(n(),E(`div`,{class:m(`${e}-switch__button-icon`),key:r?`checked-icon`:`icon`},[h(()=>r||t)],2)):!this.checked&&(i||t)?(n(),E(`div`,{class:m(`${e}-switch__button-icon`),key:i?`unchecked-icon`:`icon`},[h(()=>i||t)],2)):null},1024)))))),h(()=>_(s,t=>t&&(n(),E(`div`,{key:`checked`,class:m(`${e}-switch__checked`)},[h(()=>t)],2)))),h(()=>_(c,t=>t&&(n(),E(`div`,{key:`unchecked`,class:m(`${e}-switch__unchecked`)},[h(()=>t)],2))))],2)],6)],46,B)}});export{U as t};