import{$t as e,C as t,Cn as n,D as r,Gn as i,K as a,Ln as o,Mt as s,Nt as c,O as l,Ot as u,P as d,Pt as f,R as p,V as m,Y as h,Yn as g,_,ar as v,cn as y,en as b,f as x,fn as S,jt as C,kt as w,r as T,rn as E,tn as D,v as O,w as k,wn as A,yr as j}from"./is-browser-dQ-mLBB2.js";import{t as M}from"./color-to-class-G4jmrDIg.js";import{Y as N}from"./index-Cc8HQXPT.js";function P(e){let{textColor2:t,primaryColorHover:n,primaryColorPressed:r,primaryColor:i,infoColor:a,successColor:o,warningColor:s,errorColor:c,baseColor:u,borderColor:d,opacityDisabled:f,tagColor:p,closeIconColor:m,closeIconColorHover:h,closeIconColorPressed:g,borderRadiusSmall:_,fontSizeMini:v,fontSizeTiny:y,fontSizeSmall:b,fontSizeMedium:x,heightMini:S,heightTiny:C,heightSmall:w,heightMedium:T,closeColorHover:E,closeColorPressed:D,buttonColor2Hover:O,buttonColor2Pressed:k,fontWeightStrong:A}=e;return{...N,closeBorderRadius:_,heightTiny:S,heightSmall:C,heightMedium:w,heightLarge:T,borderRadius:_,opacityDisabled:f,fontSizeTiny:v,fontSizeSmall:y,fontSizeMedium:b,fontSizeLarge:x,fontWeightStrong:A,textColorCheckable:t,textColorHoverCheckable:t,textColorPressedCheckable:t,textColorChecked:u,colorCheckable:`#0000`,colorHoverCheckable:O,colorPressedCheckable:k,colorChecked:i,colorCheckedHover:n,colorCheckedPressed:r,border:`1px solid ${d}`,textColor:t,color:p,colorBordered:`rgb(250, 250, 252)`,closeIconColor:m,closeIconColorHover:h,closeIconColorPressed:g,closeColorHover:E,closeColorPressed:D,borderPrimary:`1px solid ${l(i,{alpha:.3})}`,textColorPrimary:i,colorPrimary:l(i,{alpha:.12}),colorBorderedPrimary:l(i,{alpha:.1}),closeIconColorPrimary:i,closeIconColorHoverPrimary:i,closeIconColorPressedPrimary:i,closeColorHoverPrimary:l(i,{alpha:.12}),closeColorPressedPrimary:l(i,{alpha:.18}),borderInfo:`1px solid ${l(a,{alpha:.3})}`,textColorInfo:a,colorInfo:l(a,{alpha:.12}),colorBorderedInfo:l(a,{alpha:.1}),closeIconColorInfo:a,closeIconColorHoverInfo:a,closeIconColorPressedInfo:a,closeColorHoverInfo:l(a,{alpha:.12}),closeColorPressedInfo:l(a,{alpha:.18}),borderSuccess:`1px solid ${l(o,{alpha:.3})}`,textColorSuccess:o,colorSuccess:l(o,{alpha:.12}),colorBorderedSuccess:l(o,{alpha:.1}),closeIconColorSuccess:o,closeIconColorHoverSuccess:o,closeIconColorPressedSuccess:o,closeColorHoverSuccess:l(o,{alpha:.12}),closeColorPressedSuccess:l(o,{alpha:.18}),borderWarning:`1px solid ${l(s,{alpha:.35})}`,textColorWarning:s,colorWarning:l(s,{alpha:.15}),colorBorderedWarning:l(s,{alpha:.12}),closeIconColorWarning:s,closeIconColorHoverWarning:s,closeIconColorPressedWarning:s,closeColorHoverWarning:l(s,{alpha:.12}),closeColorPressedWarning:l(s,{alpha:.18}),borderError:`1px solid ${l(c,{alpha:.23})}`,textColorError:c,colorError:l(c,{alpha:.1}),colorBorderedError:l(c,{alpha:.08}),closeIconColorError:c,closeIconColorHoverError:c,closeIconColorPressedError:c,closeColorHoverError:l(c,{alpha:.12}),closeColorPressedError:l(c,{alpha:.18})}}var F={name:`Tag`,common:r,self:P},I={color:Object,type:{type:String,default:`default`},round:Boolean,size:String,closable:Boolean,disabled:{type:Boolean,default:void 0}},L=w(`tag`,`
 --n-close-margin: var(--n-close-margin-top) var(--n-close-margin-right) var(--n-close-margin-bottom) var(--n-close-margin-left);
 white-space: nowrap;
 position: relative;
 box-sizing: border-box;
 cursor: default;
 display: inline-flex;
 align-items: center;
 flex-wrap: nowrap;
 padding: var(--n-padding);
 border-radius: var(--n-border-radius);
 color: var(--n-text-color);
 background-color: var(--n-color);
 transition: 
 border-color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 line-height: 1;
 height: var(--n-height);
 font-size: var(--n-font-size);
`,[s(`strong`,`
 font-weight: var(--n-font-weight-strong);
 `),C(`border`,`
 pointer-events: none;
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 border-radius: inherit;
 border: var(--n-border);
 transition: border-color .3s var(--n-bezier);
 `),C(`icon`,`
 display: flex;
 margin: 0 4px 0 0;
 color: var(--n-text-color);
 transition: color .3s var(--n-bezier);
 font-size: var(--n-avatar-size-override);
 `),C(`avatar`,`
 display: flex;
 margin: 0 6px 0 0;
 `),C(`close`,`
 margin: var(--n-close-margin);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 `),s(`round`,`
 padding: 0 calc(var(--n-height) / 3);
 border-radius: calc(var(--n-height) / 2);
 `,[C(`icon`,`
 margin: 0 4px 0 calc((var(--n-height) - 8px) / -2);
 `),C(`avatar`,`
 margin: 0 6px 0 calc((var(--n-height) - 8px) / -2);
 `),s(`closable`,`
 padding: 0 calc(var(--n-height) / 4) 0 calc(var(--n-height) / 3);
 `)]),s(`icon, avatar`,[s(`round`,`
 padding: 0 calc(var(--n-height) / 3) 0 calc(var(--n-height) / 2);
 `)]),s(`disabled`,`
 cursor: not-allowed !important;
 opacity: var(--n-opacity-disabled);
 `),s(`checkable`,`
 cursor: pointer;
 box-shadow: none;
 color: var(--n-text-color-checkable);
 background-color: var(--n-color-checkable);
 `,[c(`disabled`,[u(`&:hover`,`background-color: var(--n-color-hover-checkable);`,[c(`checked`,`color: var(--n-text-color-hover-checkable);`)]),u(`&:active`,`background-color: var(--n-color-pressed-checkable);`,[c(`checked`,`color: var(--n-text-color-pressed-checkable);`)])]),s(`checked`,`
 color: var(--n-text-color-checked);
 background-color: var(--n-color-checked);
 `,[c(`disabled`,[u(`&:hover`,`background-color: var(--n-color-checked-hover);`),u(`&:active`,`background-color: var(--n-color-checked-pressed);`)])])])]);S(),v(),o();var R=[`onClick`,`onMouseenter`,`onMouseleave`],z={...t.props,...I,bordered:{type:Boolean,default:void 0},checked:Boolean,checkable:Boolean,strong:Boolean,triggerClickOnClose:Boolean,onClose:[Array,Function],onMouseenter:Function,onMouseleave:Function,"onUpdate:checked":Function,onUpdateChecked:Function,internalCloseFocusable:{type:Boolean,default:!0},internalCloseIsButtonTag:{type:Boolean,default:!0},onCheckedChange:Function},B=h(`n-tag`),V=y({name:`Tag`,props:z,slots:Object,setup(n){let r=i(null),{mergedBorderedRef:o,mergedClsPrefixRef:s,inlineThemeDisabled:c,mergedRtlRef:l,mergedComponentPropsRef:u}=a(n),p=e(()=>n.size||u?.value?.Tag?.size||`medium`),m=t(`Tag`,`-tag`,L,F,n,s);A(B,{roundRef:g(n,`round`)});function h(){if(!n.disabled&&n.checkable){let{checked:e,onCheckedChange:t,onUpdateChecked:r,"onUpdate:checked":i}=n;r&&r(!e),i&&i(!e),t&&t(!e)}}function _(e){if(n.triggerClickOnClose||e.stopPropagation(),!n.disabled){let{onClose:t}=n;t&&O(t,e)}}let v={setTextContent(e){let{value:t}=r;t&&(t.textContent=e)}},y=x(`Tag`,l,s),b=e(()=>{let{type:e,color:{color:t,textColor:r}={}}=n,i=p.value,{common:{cubicBezierEaseInOut:a},self:{padding:s,closeMargin:c,borderRadius:l,opacityDisabled:u,textColorCheckable:h,textColorHoverCheckable:g,textColorPressedCheckable:_,textColorChecked:v,colorCheckable:y,colorHoverCheckable:b,colorPressedCheckable:x,colorChecked:S,colorCheckedHover:C,colorCheckedPressed:w,closeBorderRadius:T,fontWeightStrong:E,[f(`colorBordered`,e)]:D,[f(`closeSize`,i)]:O,[f(`closeIconSize`,i)]:k,[f(`fontSize`,i)]:A,[f(`height`,i)]:j,[f(`color`,e)]:M,[f(`textColor`,e)]:N,[f(`border`,e)]:P,[f(`closeIconColor`,e)]:F,[f(`closeIconColorHover`,e)]:I,[f(`closeIconColorPressed`,e)]:L,[f(`closeColorHover`,e)]:R,[f(`closeColorPressed`,e)]:z}}=m.value,B=d(c);return{"--n-font-weight-strong":E,"--n-avatar-size-override":`calc(${j} - 8px)`,"--n-bezier":a,"--n-border-radius":l,"--n-border":P,"--n-close-icon-size":k,"--n-close-color-pressed":z,"--n-close-color-hover":R,"--n-close-border-radius":T,"--n-close-icon-color":F,"--n-close-icon-color-hover":I,"--n-close-icon-color-pressed":L,"--n-close-icon-color-disabled":F,"--n-close-margin-top":B.top,"--n-close-margin-right":B.right,"--n-close-margin-bottom":B.bottom,"--n-close-margin-left":B.left,"--n-close-size":O,"--n-color":t||(o.value?D:M),"--n-color-checkable":y,"--n-color-checked":S,"--n-color-checked-hover":C,"--n-color-checked-pressed":w,"--n-color-hover-checkable":b,"--n-color-pressed-checkable":x,"--n-font-size":A,"--n-height":j,"--n-opacity-disabled":u,"--n-padding":s,"--n-text-color":r||N,"--n-text-color-checkable":h,"--n-text-color-checked":v,"--n-text-color-hover-checkable":g,"--n-text-color-pressed-checkable":_}}),S=c?k(`tag`,e(()=>{let e=``,{type:t,color:{color:r,textColor:i}={}}=n;return e+=t[0],e+=p.value[0],r&&(e+=`a${M(r)}`),i&&(e+=`b${M(i)}`),o.value&&(e+=`c`),e}),b,n):void 0;return{...v,rtlEnabled:y,mergedClsPrefix:s,contentRef:r,mergedBordered:o,handleClick:h,handleCloseClick:_,cssVars:c?void 0:b,themeClass:S?.themeClass,onRender:S?.onRender}},render(){let{mergedClsPrefix:e,rtlEnabled:t,closable:r,color:{borderColor:i}={},round:a,onRender:o,$slots:s}=this;o?.();let c=_(s.avatar,t=>t&&(n(),E(`div`,{class:p(`${e}-tag__avatar`)},[m(()=>t)],2))),l=_(s.icon,t=>t&&(n(),E(`div`,{class:p(`${e}-tag__icon`)},[m(()=>t)],2)));return n(),E(`div`,{class:p([`${e}-tag`,this.themeClass,{[`${e}-tag--rtl`]:t,[`${e}-tag--strong`]:this.strong,[`${e}-tag--disabled`]:this.disabled,[`${e}-tag--checkable`]:this.checkable,[`${e}-tag--checked`]:this.checkable&&this.checked,[`${e}-tag--round`]:a,[`${e}-tag--avatar`]:c,[`${e}-tag--icon`]:l,[`${e}-tag--closable`]:r}]),style:j(this.cssVars),onClick:this.handleClick,onMouseenter:this.onMouseenter,onMouseleave:this.onMouseleave},[m(()=>l||c),b(`span`,{class:p(`${e}-tag__content`),ref:`contentRef`},[m(()=>this.$slots.default?.())],2),!this.checkable&&r?(n(),D(T,{key:0,clsPrefix:e,class:p(`${e}-tag__close`),disabled:this.disabled,onClick:this.handleCloseClick,focusable:this.internalCloseFocusable,round:a,isButtonTag:this.internalCloseIsButtonTag,absolute:!0},null,8,[`clsPrefix`,`class`,`disabled`,`onClick`,`focusable`,`round`,`isButtonTag`])):m(()=>null),!this.checkable&&this.mergedBordered?(n(),E(`div`,{key:2,class:p(`${e}-tag__border`),style:j({borderColor:i})},null,6)):m(()=>null)],46,R)}});export{B as n,V as t};