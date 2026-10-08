import{$t as e,C as t,Cn as n,Ft as r,Gn as i,It as a,K as o,Ln as s,Mn as c,Ot as l,Pt as u,R as d,V as f,Y as p,_ as m,ar as h,cn as g,fn as _,h as v,jn as y,jt as b,kt as x,pn as S,rn as C,s as w,t as T,tn as E,un as D,w as O,xn as k,yn as A,yr as j}from"./is-browser-dQ-mLBB2.js";import{t as M}from"./color-to-class-G4jmrDIg.js";import{n as N}from"./Tag-DSX2T2O4.js";import{B as P}from"./index-Cc8HQXPT.js";var F=T&&`loading`in document.createElement(`img`);function I(e={}){let{root:t=null}=e;return{hash:`${e.rootMargin||`0px 0px 0px 0px`}-${Array.isArray(e.threshold)?e.threshold.join(`,`):e.threshold??`0`}`,options:{...e,root:(typeof t==`string`?document.querySelector(t):t)||document.documentElement}}}var L=new WeakMap,R=new WeakMap,z=new WeakMap,B=(e,t,n)=>{if(!e)return()=>{};let r=I(t),{root:i}=r.options,a,o=L.get(i);o?a=o:(a=new Map,L.set(i,a));let s,c;a.has(r.hash)?(c=a.get(r.hash),c[1].has(e)||(s=c[0],c[1].add(e),s.observe(e))):(s=new IntersectionObserver(e=>{e.forEach(e=>{if(e.isIntersecting){let t=R.get(e.target),n=z.get(e.target);t&&t(),n&&(n.value=!0)}})},r.options),s.observe(e),c=[s,new Set([e])],a.set(r.hash,c));let l=!1,u=()=>{l||(R.delete(e),z.delete(e),l=!0,c[1].has(e)&&(c[0].unobserve(e),c[1].delete(e)),c[1].size<=0&&a.delete(r.hash),a.size||L.delete(i))};return R.set(e,u),z.set(e,n),u},V=p(`n-avatar-group`),H=x(`avatar`,`
 width: var(--n-merged-size);
 height: var(--n-merged-size);
 color: #FFF;
 font-size: var(--n-font-size);
 display: inline-flex;
 position: relative;
 overflow: hidden;
 text-align: center;
 border: var(--n-border);
 border-radius: var(--n-border-radius);
 --n-merged-color: var(--n-color);
 background-color: var(--n-merged-color);
 transition:
 border-color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
`,[r(l(`&`,`--n-merged-color: var(--n-color-modal);`)),a(l(`&`,`--n-merged-color: var(--n-color-popover);`)),l(`img`,`
 width: 100%;
 height: 100%;
 `),b(`text`,`
 white-space: nowrap;
 display: inline-block;
 position: absolute;
 left: 50%;
 top: 50%;
 `),x(`icon`,`
 vertical-align: bottom;
 font-size: calc(var(--n-merged-size) - 6px);
 `),b(`text`,`line-height: 1.25`)]);_(),h(),s();var U=[`src`],W={...t.props,size:[String,Number],src:String,circle:{type:Boolean,default:void 0},objectFit:String,round:{type:Boolean,default:void 0},bordered:{type:Boolean,default:void 0},onError:Function,fallbackSrc:String,intersectionObserverOptions:Object,lazy:Boolean,onLoad:Function,renderPlaceholder:Function,renderFallback:Function,imgProps:Object,color:String},G=g({name:`Avatar`,props:W,slots:Object,setup(n){let{mergedClsPrefixRef:r,inlineThemeDisabled:a}=o(n),s=i(!1),l=null,d=i(null),f=i(null),p=()=>{let{value:e}=d;if(e&&(l===null||l!==e.innerHTML)){l=e.innerHTML;let{value:t}=f;if(t){let{offsetWidth:n,offsetHeight:r}=t,{offsetWidth:i,offsetHeight:a}=e,o=.9,s=Math.min(n/i*o,r/a*o,1);e.style.transform=`translateX(-50%) translateY(-50%) scale(${s})`}}},m=S(V,null),h=e(()=>{let{size:e}=n;if(e)return e;let{size:t}=m||{};return t||`medium`}),g=t(`Avatar`,`-avatar`,H,P,n,r),_=S(N,null),v=e(()=>{if(m)return!0;let{round:e,circle:t}=n;return e!==void 0||t!==void 0?e||t:_?_.roundRef.value:!1}),b=e(()=>m?!0:n.bordered||!1),x=e(()=>{let e=h.value,t=v.value,r=b.value,{color:i}=n,{self:{borderRadius:a,fontSize:o,color:s,border:c,colorModal:l,colorPopover:d},common:{cubicBezierEaseInOut:f}}=g.value,p;return p=typeof e==`number`?`${e}px`:g.value.self[u(`height`,e)],{"--n-font-size":o,"--n-border":r?c:`none`,"--n-border-radius":t?`50%`:a,"--n-color":i||s,"--n-color-modal":i||l,"--n-color-popover":i||d,"--n-bezier":f,"--n-merged-size":`var(--n-avatar-size-override, ${p})`}}),C=a?O(`avatar`,e(()=>{let e=h.value,t=v.value,r=b.value,{color:i}=n,a=``;return e&&(a+=typeof e==`number`?`a${e}`:e[0]),t&&(a+=`b`),r&&(a+=`c`),i&&(a+=M(i)),a}),x,n):void 0,w=i(!n.lazy);k(()=>{if(n.lazy&&n.intersectionObserverOptions){let e,t=c(()=>{e?.(),e=void 0,n.lazy&&(e=B(f.value,n.intersectionObserverOptions,w))});A(()=>{t(),e?.()})}}),y(()=>n.src||n.imgProps?.src,()=>{s.value=!1});let T=i(!n.lazy);return{textRef:d,selfRef:f,mergedRoundRef:v,mergedClsPrefix:r,fitTextTransform:p,cssVars:a?void 0:x,themeClass:C?.themeClass,onRender:C?.onRender,hasLoadError:s,shouldStartLoading:w,loaded:T,mergedOnError:e=>{if(!w.value)return;s.value=!0;let{onError:t,imgProps:{onError:r}={}}=n;t?.(e),r?.(e)},mergedOnLoad:e=>{let{onLoad:t,imgProps:{onLoad:r}={}}=n;t?.(e),r?.(e),T.value=!0}}},render(){let{$slots:e,src:t,mergedClsPrefix:r,lazy:i,onRender:a,loaded:o,hasLoadError:s,imgProps:c={}}=this;a?.();let l,u=!o&&!s&&(this.renderPlaceholder?this.renderPlaceholder():this.$slots.placeholder?.());return l=this.hasLoadError?this.renderFallback?this.renderFallback():v(e.fallback,()=>[(n(),C(`img`,{src:this.fallbackSrc,style:j({objectFit:this.objectFit})},null,12,U))]):m(e.default,e=>{if(e)return n(),E(w,{key:1,onResize:this.fitTextTransform},{default:()=>(n(),C(`span`,{ref:`textRef`,class:d(`${r}-avatar__text`)},[f(()=>e)],2))},1032,[`onResize`]);if(t||c.src){let e=this.src||c.src;return D(`img`,{...c,loading:F&&!this.intersectionObserverOptions&&i?`lazy`:`eager`,src:i&&this.intersectionObserverOptions?this.shouldStartLoading?e:void 0:e,"data-image-src":e,onLoad:this.mergedOnLoad,onError:this.mergedOnError,style:[c.style||``,{objectFit:this.objectFit},u?{height:`0`,width:`0`,visibility:`hidden`,position:`absolute`}:``]})}}),n(),C(`span`,{ref:`selfRef`,class:d([`${r}-avatar`,this.themeClass]),style:j(this.cssVars)},[f(()=>l),f(()=>i&&u)],6)}});export{G as t};