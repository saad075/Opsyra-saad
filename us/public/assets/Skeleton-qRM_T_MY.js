import{$t as e,C as t,Cn as n,D as r,F as i,K as a,Ot as o,Pt as s,V as c,Yt as l,cn as u,fn as d,hn as f,kt as p,rn as m,t as h,un as g}from"./is-browser-dQ-mLBB2.js";import{c as _}from"./client-BpOzf98Y.js";var v=!1;function y(){if(h&&window.CSS&&!v&&(v=!0,`registerProperty`in window?.CSS))try{CSS.registerProperty({name:`--n-color-start`,syntax:`<color>`,inherits:!1,initialValue:`#0000`}),CSS.registerProperty({name:`--n-color-end`,syntax:`<color>`,inherits:!1,initialValue:`#0000`})}catch{}}function b(e){let{heightSmall:t,heightMedium:n,heightLarge:r,borderRadius:i}=e;return{color:`#eee`,colorEnd:`#ddd`,borderRadius:i,heightSmall:t,heightMedium:n,heightLarge:r}}var x={name:`Skeleton`,common:r,self:b},S=o([p(`skeleton`,`
 height: 1em;
 width: 100%;
 transition:
 --n-color-start .3s var(--n-bezier),
 --n-color-end .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 animation: 2s skeleton-loading infinite cubic-bezier(0.36, 0, 0.64, 1);
 background-color: var(--n-color-start);
 `),o(`@keyframes skeleton-loading`,`
 0% {
 background: var(--n-color-start);
 }
 40% {
 background: var(--n-color-end);
 }
 80% {
 background: var(--n-color-start);
 }
 100% {
 background: var(--n-color-start);
 }
 `)]);d();var C={...t.props,text:Boolean,round:Boolean,circle:Boolean,height:[String,Number],width:[String,Number],size:String,repeat:{type:Number,default:1},animated:{type:Boolean,default:!0},sharp:{type:Boolean,default:!0}},w=u({name:`Skeleton`,inheritAttrs:!1,props:C,setup(n){y();let{mergedClsPrefixRef:r,mergedComponentPropsRef:o}=a(n),c=e(()=>n.size||o?.value?.Skeleton?.size),l=t(`Skeleton`,`-skeleton`,S,x,n,r);return{mergedClsPrefix:r,style:e(()=>{let e=l.value,{common:{cubicBezierEaseInOut:t}}=e,r=e.self,{color:a,colorEnd:o,borderRadius:u}=r,d,{circle:f,sharp:p,round:m,width:h,height:g,text:_,animated:v}=n,y=c.value;y!==void 0&&(d=r[s(`height`,y)]);let b=f?h??g??d:h,x=(f?h??g:g)??d;return{display:_?`inline-block`:``,verticalAlign:_?`-0.125em`:``,borderRadius:f?`50%`:m?`4096px`:p?``:u,width:typeof b==`number`?i(b):b,height:typeof x==`number`?i(x):x,animation:v?``:`none`,"--n-bezier":t,"--n-color-start":a,"--n-color-end":o}})}},render(){let{repeat:e,style:t,mergedClsPrefix:r,$attrs:i}=this,a=g(`div`,f({class:`${r}-skeleton`,style:t},i));return e>1?(n(),m(l,{key:1},[c(()=>_(e,null).map(e=>[a,`
`]))],64)):a}});export{w as t};