import{$t as e,C as t,Cn as n,Gn as r,Ht as i,K as a,Ln as o,Mt as s,Nt as c,Ot as l,Pt as u,Q as d,R as f,V as p,Y as m,Yn as h,Yt as g,cn as _,en as v,fn as y,hn as b,jn as x,jt as S,kt as C,o as w,pn as T,rn as E,tn as D,un as O,v as k,w as A,wn as j,z as M,zt as N}from"./is-browser-dQ-mLBB2.js";import{a as P,c as F,f as I,i as L,o as R,r as z,s as ee,t as te}from"./create-0yRyaERx.js";import{t as B}from"./use-memo-Dodol12_.js";import{t as ne}from"./use-merged-state-BNqaTJd4.js";import{t as re}from"./use-keyboard-CGzuon3p.js";import{t as ie}from"./ChevronRight-D9Z-dtB9.js";import{t as ae}from"./Icon-z7hyG2Ko.js";import{H as V,V as oe,at as se,it as H,k as ce,nt as le,ot as U}from"./index-Cc8HQXPT.js";function W(e){return t=>{e.value=t?t.$el:null}}var G=m(`n-dropdown-menu`),K=m(`n-dropdown`),q=m(`n-dropdown-option`);y();var J=_({name:`DropdownDivider`,props:{clsPrefix:{type:String,required:!0}},render(){return n(),E(`div`,{class:f(`${this.clsPrefix}-dropdown-divider`)},null,2)}});function Y(e,t){return e.type===`submenu`||e.type===void 0&&e[t]!==void 0}function ue(e){return e.type===`group`}function X(e){return e.type===`divider`}function de(e){return e.type===`render`}o(),y();function fe(e,t,n){if(!t)return e;let i=r(e.value),a=null;return x(e,e=>{a!==null&&window.clearTimeout(a),e===!0?n&&!n.value?i.value=!0:a=window.setTimeout(()=>{i.value=!0},t):i.value=!1}),i}y(),i(),o();var Z=_({name:`DropdownOption`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0},parentKey:{type:[String,Number],default:null},placement:{type:String,default:`right-start`},props:Object,scrollable:Boolean},setup(t){let n=T(K),{hoverKeyRef:i,keyboardKeyRef:a,lastToggledSubmenuKeyRef:o,pendingKeyPathRef:s,activeKeyPathRef:c,animatedRef:l,mergedShowRef:u,renderLabelRef:d,renderIconRef:f,labelFieldRef:p,childrenFieldRef:m,renderOptionRef:h,nodePropsRef:g,menuPropsRef:_}=n,v=T(q,null),y=T(G),b=T(H),x=e(()=>t.tmNode.rawNode),S=e(()=>{let{value:e}=m;return Y(t.tmNode.rawNode,e)}),C=e(()=>{let{disabled:e}=t.tmNode;return e}),w=fe(e(()=>{if(!S.value)return!1;let{key:e,disabled:n}=t.tmNode;if(n)return!1;let{value:r}=i,{value:c}=a,{value:l}=o,{value:u}=s;return r===null?c===null?l!==null&&u.includes(e):u.includes(e)&&u[u.length-1]!==e:u.includes(e)}),300,e(()=>a.value===null&&!l.value)),E=e(()=>!!v?.enteringSubmenuRef.value),D=r(!1);j(q,{enteringSubmenuRef:D});function O(){D.value=!0}function k(){D.value=!1}function A(){let{parentKey:e,tmNode:n}=t;n.disabled||u.value&&(o.value=e,a.value=null,i.value=n.key)}function M(){let{tmNode:e}=t;e.disabled||u.value&&i.value!==e.key&&A()}function N(e){if(t.tmNode.disabled||!u.value)return;let{relatedTarget:n}=e;n&&!I({target:n},`dropdownOption`)&&!I({target:n},`scrollbarRail`)&&(i.value=null)}function P(){let{value:e}=S,{tmNode:r}=t;u.value&&!e&&!r.disabled&&(n.doSelect(r.key,r.rawNode),n.doUpdateShow(!1))}return{labelField:p,renderLabel:d,renderIcon:f,siblingHasIcon:y.showIconRef,siblingHasSubmenu:y.hasSubmenuRef,menuProps:_,popoverBody:b,animated:l,mergedShowSubmenu:e(()=>w.value&&!E.value),rawNode:x,hasSubmenu:S,pending:B(()=>{let{value:e}=s,{key:n}=t.tmNode;return e.includes(n)}),childActive:B(()=>{let{value:e}=c,{key:n}=t.tmNode,r=e.findIndex(e=>n===e);return r!==-1&&r<e.length-1}),active:B(()=>{let{value:e}=c,{key:n}=t.tmNode,r=e.findIndex(e=>n===e);return r!==-1&&r===e.length-1}),mergedDisabled:C,renderOption:h,nodeProps:g,handleClick:P,handleMouseMove:M,handleMouseEnter:A,handleMouseLeave:N,handleSubmenuBeforeEnter:O,handleSubmenuAfterEnter:k}},render(){let{animated:e,rawNode:t,mergedShowSubmenu:r,clsPrefix:i,siblingHasIcon:a,siblingHasSubmenu:o,renderLabel:s,renderIcon:c,renderOption:l,nodeProps:u,props:d,scrollable:m}=this,h=null;if(r){let e=this.menuProps?.(t,t.children);h=(t=>(n(),D($,b({key:1},e,{clsPrefix:i,scrollable:this.scrollable,tmNodes:this.tmNode.children,parentKey:this.tmNode.key}),null,16,[`clsPrefix`,`scrollable`,`tmNodes`,`parentKey`])))(h)}let _={class:[`${i}-dropdown-option-body`,this.pending&&`${i}-dropdown-option-body--pending`,this.active&&`${i}-dropdown-option-body--active`,this.childActive&&`${i}-dropdown-option-body--child-active`,this.mergedDisabled&&`${i}-dropdown-option-body--disabled`],onMousemove:this.handleMouseMove,onMouseenter:this.handleMouseEnter,onMouseleave:this.handleMouseLeave,onClick:this.handleClick},v=u?.(t),y=(n(),E(`div`,b({class:[`${i}-dropdown-option`,v?.class],"data-dropdown-option":!0},v),[p(()=>O(`div`,b(_,d),[(n(),E(`div`,{class:f([`${i}-dropdown-option-body__prefix`,a&&`${i}-dropdown-option-body__prefix--show-icon`])},[p(()=>[c?c(t):V(t.icon)])],2)),(n(),E(`div`,{"data-dropdown-option":!0,class:f(`${i}-dropdown-option-body__label`)},[s?(n(),E(g,{key:0},[p(()=>s(t))],64)):(n(),E(g,{key:1},[p(()=>V(t[this.labelField]??t.title))],64))],2)),(n(),E(`div`,{"data-dropdown-option":!0,class:f([`${i}-dropdown-option-body__suffix`,o&&`${i}-dropdown-option-body__suffix--has-submenu`])},[this.hasSubmenu?(n(),D(ae,{key:0},{_:1,default:M(()=>(n(),D(ie)))})):p(()=>null)],2))])),this.hasSubmenu?(n(),D(F,{key:0},{default:()=>[(n(),D(ee,null,{default:()=>(n(),E(`div`,{class:f(`${i}-dropdown-offset-container`)},[(n(),D(R,{show:this.mergedShowSubmenu,placement:this.placement,to:m&&this.popoverBody||void 0,teleportDisabled:!m},{default:()=>(n(),E(`div`,{class:f(`${i}-dropdown-menu-wrapper`)},[e?(n(),D(N,{key:0,onBeforeEnter:this.handleSubmenuBeforeEnter,onAfterEnter:this.handleSubmenuAfterEnter,name:`fade-in-scale-up-transition`,appear:!0},{default:()=>h},1032,[`onBeforeEnter`,`onAfterEnter`])):(n(),E(g,{key:1},[p(()=>h)],64))],2))},1032,[`show`,`placement`,`to`,`teleportDisabled`]))],2))},1024))]},1024)):p(()=>null)],16));return l?l({node:y,option:t}):y}});y();var pe=_({name:`DropdownGroupHeader`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(){let{showIconRef:e,hasSubmenuRef:t}=T(G),{renderLabelRef:n,labelFieldRef:r,nodePropsRef:i,renderOptionRef:a}=T(K);return{labelField:r,showIcon:e,hasSubmenu:t,renderLabel:n,nodeProps:i,renderOption:a}},render(){let{clsPrefix:e,hasSubmenu:t,showIcon:r,nodeProps:i,renderLabel:a,renderOption:o}=this,{rawNode:s}=this.tmNode,c=(n(),E(`div`,b({class:`${e}-dropdown-option`},i?.(s)),[v(`div`,{class:f(`${e}-dropdown-option-body ${e}-dropdown-option-body--group`)},[v(`div`,{"data-dropdown-option":!0,class:f([`${e}-dropdown-option-body__prefix`,r&&`${e}-dropdown-option-body__prefix--show-icon`])},[p(()=>V(s.icon))],2),v(`div`,{class:f(`${e}-dropdown-option-body__label`),"data-dropdown-option":!0},[a?(n(),E(g,{key:0},[p(()=>a(s))],64)):(n(),E(g,{key:1},[p(()=>V(s.title??s[this.labelField]))],64))],2),v(`div`,{class:f([`${e}-dropdown-option-body__suffix`,t&&`${e}-dropdown-option-body__suffix--has-submenu`]),"data-dropdown-option":!0},null,2)],2)],16));return o?o({node:c,option:s}):c}});y();var me=_({name:`NDropdownGroup`,props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0},parentKey:{type:[String,Number],default:null}},render(){let{tmNode:e,parentKey:t,clsPrefix:r}=this,{children:i}=e;return n(),E(g,null,[(n(),D(pe,{clsPrefix:r,tmNode:e,key:e.key},null,8,[`clsPrefix`,`tmNode`])),p(()=>i?.map(e=>{let{rawNode:i}=e;return i.show===!1?null:X(i)?O(J,{clsPrefix:r,key:e.key}):e.isGroup?(d(`dropdown`,"`group` node is not allowed to be put in `group` node."),null):(n(),D(Z,{clsPrefix:r,tmNode:e,parentKey:t,key:e.key},null,8,[`clsPrefix`,`tmNode`,`parentKey`]))}))],64)}});y();var Q=_({name:`DropdownRenderOption`,props:{tmNode:{type:Object,required:!0}},render(){let{rawNode:{render:e,props:t}}=this.tmNode;return O(`div`,t,[e?.()])}});y(),o();var $=_({name:`DropdownMenu`,props:{scrollable:Boolean,showArrow:Boolean,arrowStyle:[String,Object],clsPrefix:{type:String,required:!0},tmNodes:{type:Array,default:()=>[]},parentKey:{type:[String,Number],default:null}},setup(t){let{renderIconRef:n,childrenFieldRef:i}=T(K);j(G,{showIconRef:e(()=>{let e=n.value;return t.tmNodes.some(t=>{if(t.isGroup)return t.children?.some(({rawNode:t})=>e?e(t):t.icon);let{rawNode:n}=t;return e?e(n):n.icon})}),hasSubmenuRef:e(()=>{let{value:e}=i;return t.tmNodes.some(t=>{if(t.isGroup)return t.children?.some(({rawNode:t})=>Y(t,e));let{rawNode:n}=t;return Y(n,e)})})});let a=r(null);return j(se,null),j(U,null),j(H,a),{bodyRef:a}},render(){let{parentKey:e,clsPrefix:t,scrollable:r}=this,i=this.tmNodes.map(i=>{let{rawNode:a}=i;return a.show===!1?null:de(a)?(n(),D(Q,{tmNode:i,key:i.key},null,8,[`tmNode`])):X(a)?(n(),D(J,{clsPrefix:t,key:i.key},null,8,[`clsPrefix`])):ue(a)?(n(),D(me,{clsPrefix:t,tmNode:i,parentKey:e,key:i.key},null,8,[`clsPrefix`,`tmNode`,`parentKey`])):(n(),D(Z,{clsPrefix:t,tmNode:i,parentKey:e,key:i.key,props:a.props,scrollable:r},null,8,[`clsPrefix`,`tmNode`,`parentKey`,`props`,`scrollable`]))});return n(),E(`div`,{class:f([`${t}-dropdown-menu`,r&&`${t}-dropdown-menu--scrollable`]),ref:`bodyRef`},[r?(n(),D(w,{key:0,contentClass:`${t}-dropdown-menu__content`},{default:()=>i},1032,[`contentClass`])):(n(),E(g,{key:1},[p(()=>i)],64)),this.showArrow?(n(),E(g,{key:2},[p(()=>P({clsPrefix:t,arrowStyle:this.arrowStyle,arrowClass:void 0,arrowWrapperClass:void 0,arrowWrapperStyle:void 0}))],64)):p(()=>null)],2)}}),he=C(`dropdown-menu`,`
 transform-origin: var(--v-transform-origin);
 background-color: var(--n-color);
 border-radius: var(--n-border-radius);
 box-shadow: var(--n-box-shadow);
 position: relative;
 transition:
 background-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
`,[oe(),C(`dropdown-option`,`
 position: relative;
 `,[l(`a`,`
 text-decoration: none;
 color: inherit;
 outline: none;
 `,[l(`&::before`,`
 content: "";
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `)]),C(`dropdown-option-body`,`
 display: flex;
 cursor: pointer;
 position: relative;
 height: var(--n-option-height);
 line-height: var(--n-option-height);
 font-size: var(--n-font-size);
 color: var(--n-option-text-color);
 transition: color .3s var(--n-bezier);
 `,[l(`&::before`,`
 content: "";
 position: absolute;
 top: 0;
 bottom: 0;
 left: 4px;
 right: 4px;
 transition: background-color .3s var(--n-bezier);
 border-radius: var(--n-border-radius);
 `),c(`disabled`,[s(`pending`,`
 color: var(--n-option-text-color-hover);
 `,[S(`prefix, suffix`,`
 color: var(--n-option-text-color-hover);
 `),l(`&::before`,`background-color: var(--n-option-color-hover);`)]),s(`active`,`
 color: var(--n-option-text-color-active);
 `,[S(`prefix, suffix`,`
 color: var(--n-option-text-color-active);
 `),l(`&::before`,`background-color: var(--n-option-color-active);`)]),s(`child-active`,`
 color: var(--n-option-text-color-child-active);
 `,[S(`prefix, suffix`,`
 color: var(--n-option-text-color-child-active);
 `)])]),s(`disabled`,`
 cursor: not-allowed;
 opacity: var(--n-option-opacity-disabled);
 `),s(`group`,`
 font-size: calc(var(--n-font-size) - 1px);
 color: var(--n-group-header-text-color);
 `,[S(`prefix`,`
 width: calc(var(--n-option-prefix-width) / 2);
 `,[s(`show-icon`,`
 width: calc(var(--n-option-icon-prefix-width) / 2);
 `)])]),S(`prefix`,`
 width: var(--n-option-prefix-width);
 display: flex;
 justify-content: center;
 align-items: center;
 color: var(--n-prefix-color);
 transition: color .3s var(--n-bezier);
 z-index: 1;
 `,[s(`show-icon`,`
 width: var(--n-option-icon-prefix-width);
 `),C(`icon`,`
 font-size: var(--n-option-icon-size);
 `)]),S(`label`,`
 white-space: nowrap;
 flex: 1;
 z-index: 1;
 `),S(`suffix`,`
 box-sizing: border-box;
 flex-grow: 0;
 flex-shrink: 0;
 display: flex;
 justify-content: flex-end;
 align-items: center;
 min-width: var(--n-option-suffix-width);
 padding: 0 8px;
 transition: color .3s var(--n-bezier);
 color: var(--n-suffix-color);
 z-index: 1;
 `,[s(`has-submenu`,`
 width: var(--n-option-icon-suffix-width);
 `),C(`icon`,`
 font-size: var(--n-option-icon-size);
 `)]),C(`dropdown-menu`,`pointer-events: all;`)]),C(`dropdown-offset-container`,`
 pointer-events: none;
 position: absolute;
 left: 0;
 right: 0;
 top: -4px;
 bottom: -4px;
 `)]),C(`dropdown-divider`,`
 transition: background-color .3s var(--n-bezier);
 background-color: var(--n-divider-color);
 height: 1px;
 margin: 4px 0;
 `),C(`dropdown-menu-wrapper`,`
 transform-origin: var(--v-transform-origin);
 width: fit-content;
 `),l(`>`,[C(`scrollbar`,`
 height: inherit;
 max-height: inherit;
 `)]),c(`scrollable`,`
 padding: var(--n-padding);
 `),s(`scrollable`,[S(`content`,`
 padding: var(--n-padding);
 `)])]);y(),o();var ge={animated:{type:Boolean,default:!0},keyboard:{type:Boolean,default:!0},size:String,inverted:Boolean,placement:{type:String,default:`bottom`},onSelect:[Function,Array],options:{type:Array,default:()=>[]},menuProps:Function,showArrow:Boolean,renderLabel:Function,renderIcon:Function,renderOption:Function,nodeProps:Function,labelField:{type:String,default:`label`},keyField:{type:String,default:`key`},childrenField:{type:String,default:`children`},value:[String,Number]},_e=Object.keys(L),ve={...L,...ge,...t.props},ye=_({name:`Dropdown`,inheritAttrs:!1,props:ve,setup(n){let i=r(!1),o=ne(h(n,`show`),i),s=e(()=>{let{keyField:e,childrenField:t}=n;return te(n.options,{getKey(t){return t[e]},getDisabled(e){return e.disabled===!0},getIgnored(e){return e.type===`divider`||e.type===`render`},getChildren(e){return e[t]}})}),c=e(()=>s.value.treeNodes),l=r(null),d=r(null),f=r(null),p=e(()=>l.value??d.value??f.value??null),m=e(()=>s.value.getPath(p.value).keyPath),g=e(()=>s.value.getPath(n.value).keyPath),_=B(()=>n.keyboard&&o.value);re({keydown:{ArrowUp:{prevent:!0,handler:N},ArrowRight:{prevent:!0,handler:M},ArrowDown:{prevent:!0,handler:P},ArrowLeft:{prevent:!0,handler:O},Enter:{prevent:!0,handler:F},Escape:D}},_);let{mergedClsPrefixRef:v,inlineThemeDisabled:y,mergedComponentPropsRef:b}=a(n),S=e(()=>n.size||b?.value?.Dropdown?.size||`medium`),C=t(`Dropdown`,`-dropdown`,he,ce,n,v);j(K,{labelFieldRef:h(n,`labelField`),childrenFieldRef:h(n,`childrenField`),renderLabelRef:h(n,`renderLabel`),renderIconRef:h(n,`renderIcon`),hoverKeyRef:l,keyboardKeyRef:d,lastToggledSubmenuKeyRef:f,pendingKeyPathRef:m,activeKeyPathRef:g,animatedRef:h(n,`animated`),mergedShowRef:o,nodePropsRef:h(n,`nodeProps`),renderOptionRef:h(n,`renderOption`),menuPropsRef:h(n,`menuProps`),doSelect:w,doUpdateShow:T}),x(o,e=>{!n.animated&&!e&&E()});function w(e,t){let{onSelect:r}=n;r&&k(r,e,t)}function T(e){let{"onUpdate:show":t,onUpdateShow:r}=n;t&&k(t,e),r&&k(r,e),i.value=e}function E(){l.value=null,d.value=null,f.value=null}function D(){T(!1)}function O(){L(`left`)}function M(){L(`right`)}function N(){L(`up`)}function P(){L(`down`)}function F(){let e=I();e?.isLeaf&&o.value&&(w(e.key,e.rawNode),T(!1))}function I(){let{value:e}=s,{value:t}=p;return!e||t===null?null:e.getNode(t)??null}function L(e){let{value:t}=p,{value:{getFirstAvailableNode:n}}=s,r=null;if(t===null){let e=n();e!==null&&(r=e.key)}else{let t=I();if(t){let n;switch(e){case`down`:n=t.getNext();break;case`up`:n=t.getPrev();break;case`right`:n=t.getChild();break;case`left`:n=t.getParent()}n&&(r=n.key)}}r!==null&&(l.value=null,d.value=r)}let R=e(()=>{let{inverted:e}=n,t=S.value,{common:{cubicBezierEaseInOut:r},self:i}=C.value,{padding:a,dividerColor:o,borderRadius:s,optionOpacityDisabled:c,[u(`optionIconSuffixWidth`,t)]:l,[u(`optionSuffixWidth`,t)]:d,[u(`optionIconPrefixWidth`,t)]:f,[u(`optionPrefixWidth`,t)]:p,[u(`fontSize`,t)]:m,[u(`optionHeight`,t)]:h,[u(`optionIconSize`,t)]:g}=i,_={"--n-bezier":r,"--n-font-size":m,"--n-padding":a,"--n-border-radius":s,"--n-option-height":h,"--n-option-prefix-width":p,"--n-option-icon-prefix-width":f,"--n-option-suffix-width":d,"--n-option-icon-suffix-width":l,"--n-option-icon-size":g,"--n-divider-color":o,"--n-option-opacity-disabled":c};return e?(_[`--n-color`]=i.colorInverted,_[`--n-option-color-hover`]=i.optionColorHoverInverted,_[`--n-option-color-active`]=i.optionColorActiveInverted,_[`--n-option-text-color`]=i.optionTextColorInverted,_[`--n-option-text-color-hover`]=i.optionTextColorHoverInverted,_[`--n-option-text-color-active`]=i.optionTextColorActiveInverted,_[`--n-option-text-color-child-active`]=i.optionTextColorChildActiveInverted,_[`--n-prefix-color`]=i.prefixColorInverted,_[`--n-suffix-color`]=i.suffixColorInverted,_[`--n-group-header-text-color`]=i.groupHeaderTextColorInverted):(_[`--n-color`]=i.color,_[`--n-option-color-hover`]=i.optionColorHover,_[`--n-option-color-active`]=i.optionColorActive,_[`--n-option-text-color`]=i.optionTextColor,_[`--n-option-text-color-hover`]=i.optionTextColorHover,_[`--n-option-text-color-active`]=i.optionTextColorActive,_[`--n-option-text-color-child-active`]=i.optionTextColorChildActive,_[`--n-prefix-color`]=i.prefixColor,_[`--n-suffix-color`]=i.suffixColor,_[`--n-group-header-text-color`]=i.groupHeaderTextColor),_}),z=y?A(`dropdown`,e(()=>`${S.value[0]}${n.inverted?`i`:``}`),R,n):void 0;return{mergedClsPrefix:v,mergedTheme:C,mergedSize:S,tmNodes:c,mergedShow:o,handleAfterLeave:()=>{n.animated&&E()},doUpdateShow:T,cssVars:y?void 0:R,themeClass:z?.themeClass,onRender:z?.onRender}},render(){let e=(e,t,n,r,i)=>{let{mergedClsPrefix:a,menuProps:o}=this;this.onRender?.();let s=o?.(void 0,this.tmNodes.map(e=>e.rawNode))||{},c={ref:W(t),class:[e,`${a}-dropdown`,`${a}-dropdown--${this.mergedSize}-size`,this.themeClass],clsPrefix:a,tmNodes:this.tmNodes,style:[...n,this.cssVars],showArrow:this.showArrow,arrowStyle:this.arrowStyle,scrollable:this.scrollable,onMouseenter:r,onMouseleave:i};return O($,b(this.$attrs,c,s))},{mergedTheme:t}=this,r={show:this.mergedShow,theme:t.peers.Popover,themeOverrides:t.peerOverrides.Popover,internalOnAfterLeave:this.handleAfterLeave,internalRenderBody:e,onUpdateShow:this.doUpdateShow,"onUpdate:show":void 0};return n(),D(z,le(this.$props,_e,r),{_:1,trigger:M(()=>this.$slots.default?.())},16)}});export{W as n,ye as t};