import{$t as e,C as t,Cn as n,K as r,Mt as i,Nt as a,R as o,V as s,Yt as c,ar as l,cn as u,en as d,fn as f,jt as p,kt as m,rn as h,w as g,yr as _}from"./is-browser-dQ-mLBB2.js";import{g as v}from"./index-Cc8HQXPT.js";var y=m(`divider`,`
 position: relative;
 display: flex;
 width: 100%;
 box-sizing: border-box;
 font-size: 16px;
 color: var(--n-text-color);
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
`,[a(`vertical`,`
 margin-top: 24px;
 margin-bottom: 24px;
 `,[a(`no-title`,`
 display: flex;
 align-items: center;
 `)]),p(`title`,`
 display: flex;
 align-items: center;
 margin-left: 12px;
 margin-right: 12px;
 white-space: nowrap;
 font-weight: var(--n-font-weight);
 `),i(`title-position-left`,[p(`line`,[i(`left`,{width:`28px`})])]),i(`title-position-right`,[p(`line`,[i(`right`,{width:`28px`})])]),i(`dashed`,[p(`line`,`
 background-color: #0000;
 height: 0px;
 width: 100%;
 border-style: dashed;
 border-width: 1px 0 0;
 `)]),i(`vertical`,`
 display: inline-block;
 height: 1em;
 margin: 0 8px;
 vertical-align: middle;
 width: 1px;
 `),p(`line`,`
 border: none;
 transition: background-color .3s var(--n-bezier), border-color .3s var(--n-bezier);
 height: 1px;
 width: 100%;
 margin: 0;
 `),a(`dashed`,[p(`line`,{backgroundColor:`var(--n-color)`})]),i(`dashed`,[p(`line`,{borderColor:`var(--n-color)`})]),i(`vertical`,{backgroundColor:`var(--n-color)`})]);f(),l();var b={...t.props,titlePlacement:{type:String,default:`center`},dashed:Boolean,vertical:Boolean},x=u({name:`Divider`,props:b,setup(n){let{mergedClsPrefixRef:i,inlineThemeDisabled:a}=r(n),o=t(`Divider`,`-divider`,y,v,n,i),s=e(()=>{let{common:{cubicBezierEaseInOut:e},self:{color:t,textColor:n,fontWeight:r}}=o.value;return{"--n-bezier":e,"--n-color":t,"--n-text-color":n,"--n-font-weight":r}}),c=a?g(`divider`,void 0,s,n):void 0;return{mergedClsPrefix:i,cssVars:a?void 0:s,themeClass:c?.themeClass,onRender:c?.onRender}},render(){let{$slots:e,titlePlacement:t,vertical:r,dashed:i,cssVars:a,mergedClsPrefix:l}=this;return this.onRender?.(),n(),h(`div`,{role:`separator`,class:o([`${l}-divider`,this.themeClass,{[`${l}-divider--vertical`]:r,[`${l}-divider--no-title`]:!e.default,[`${l}-divider--dashed`]:i,[`${l}-divider--title-position-${t}`]:e.default&&t}]),style:_(a)},[r?s(()=>null):(n(),h(`div`,{key:0,class:o(`${l}-divider__line ${l}-divider__line--left`)},null,2)),!r&&e.default?(n(),h(c,{key:2},[d(`div`,{class:o(`${l}-divider__title`)},[s(()=>this.$slots.default?.())],2),d(`div`,{class:o(`${l}-divider__line ${l}-divider__line--right`)},null,2)],64)):s(()=>null)],6)}});export{x as t};