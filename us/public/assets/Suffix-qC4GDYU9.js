import{Cn as e,H as t,L as n,Ln as r,Ot as i,R as a,V as o,Yn as s,cn as c,en as l,fn as u,h as d,i as f,jt as p,kt as m,rn as h,sn as g,tn as _,x as v}from"./is-browser-dQ-mLBB2.js";import{c as y,l as b,s as x}from"./Button-BWtrbvO5.js";u();var S=f(`clear`,()=>(()=>{let e=n(`c93f8499adf26ca3`);return e[0]||=l(`svg`,{viewBox:`0 0 16 16`,version:`1.1`,xmlns:`http://www.w3.org/2000/svg`},[l(`g`,{stroke:`none`,"stroke-width":`1`,fill:`none`,"fill-rule":`evenodd`},[l(`g`,{fill:`currentColor`,"fill-rule":`nonzero`},[l(`path`,{d:`M8,2 C11.3137085,2 14,4.6862915 14,8 C14,11.3137085 11.3137085,14 8,14 C4.6862915,14 2,11.3137085 2,8 C2,4.6862915 4.6862915,2 8,2 Z M6.5343055,5.83859116 C6.33943736,5.70359511 6.07001296,5.72288026 5.89644661,5.89644661 L5.89644661,5.89644661 L5.83859116,5.9656945 C5.70359511,6.16056264 5.72288026,6.42998704 5.89644661,6.60355339 L5.89644661,6.60355339 L7.293,8 L5.89644661,9.39644661 L5.83859116,9.4656945 C5.70359511,9.66056264 5.72288026,9.92998704 5.89644661,10.1035534 L5.89644661,10.1035534 L5.9656945,10.1614088 C6.16056264,10.2964049 6.42998704,10.2771197 6.60355339,10.1035534 L6.60355339,10.1035534 L8,8.707 L9.39644661,10.1035534 L9.4656945,10.1614088 C9.66056264,10.2964049 9.92998704,10.2771197 10.1035534,10.1035534 L10.1035534,10.1035534 L10.1614088,10.0343055 C10.2964049,9.83943736 10.2771197,9.57001296 10.1035534,9.39644661 L10.1035534,9.39644661 L8.707,8 L10.1035534,6.60355339 L10.1614088,6.5343055 C10.2964049,6.33943736 10.2771197,6.07001296 10.1035534,5.89644661 L10.1035534,5.89644661 L10.0343055,5.83859116 C9.83943736,5.70359511 9.57001296,5.72288026 9.39644661,5.89644661 L9.39644661,5.89644661 L8,7.293 L6.60355339,5.89644661 Z`})])])],-1)})()),C=m(`base-clear`,`
 flex-shrink: 0;
 height: 1em;
 width: 1em;
 position: relative;
`,[i(`>`,[p(`clear`,`
 font-size: var(--n-clear-size);
 height: 1em;
 width: 1em;
 cursor: pointer;
 color: var(--n-clear-color);
 transition: color .3s var(--n-bezier);
 display: flex;
 `,[i(`&:hover`,`
 color: var(--n-clear-color-hover)!important;
 `),i(`&:active`,`
 color: var(--n-clear-color-pressed)!important;
 `)]),p(`placeholder`,`
 display: flex;
 `),p(`clear, placeholder`,`
 position: absolute;
 left: 50%;
 top: 50%;
 transform: translateX(-50%) translateY(-50%);
 `,[y({originalTransform:`translateX(-50%) translateY(-50%)`,left:`50%`,top:`50%`})])])]);u(),r();var w=[`onClick`,`onMousedown`],T=c({name:`BaseClear`,props:{clsPrefix:{type:String,required:!0},show:Boolean,onClear:Function},setup(e){return t(`-base-clear`,C,s(e,`clsPrefix`)),{handleMouseDown(e){e.preventDefault()}}},render(){let{clsPrefix:t}=this;return e(),h(`div`,{class:a(`${t}-base-clear`)},[g(b,null,{default:()=>this.show?(e(),h(`div`,{key:`dismiss`,class:a(`${t}-base-clear__clear`),onClick:this.onClear,onMousedown:this.handleMouseDown,"data-clear":!0},[o(()=>d(this.$slots.icon,()=>[(e(),_(v,{clsPrefix:t},{default:()=>(e(),_(S))},1032,[`clsPrefix`]))]))],42,w)):(e(),h(`div`,{key:`icon`,class:a(`${t}-base-clear__placeholder`)},[o(()=>this.$slots.placeholder?.())],2))},1024)],2)}});u();var E=c({name:`ChevronDown`,render(){return(()=>{let e=n(`ae90ecf811a811ac`);return e[0]||=l(`svg`,{viewBox:`0 0 16 16`,fill:`none`,xmlns:`http://www.w3.org/2000/svg`},[l(`path`,{d:`M3.14645 5.64645C3.34171 5.45118 3.65829 5.45118 3.85355 5.64645L8 9.79289L12.1464 5.64645C12.3417 5.45118 12.6583 5.45118 12.8536 5.64645C13.0488 5.84171 13.0488 6.15829 12.8536 6.35355L8.35355 10.8536C8.15829 11.0488 7.84171 11.0488 7.64645 10.8536L3.14645 6.35355C2.95118 6.15829 2.95118 5.84171 3.14645 5.64645Z`,fill:`currentColor`})],-1)})()}});u();var D=c({name:`InternalSelectionSuffix`,props:{clsPrefix:{type:String,required:!0},showArrow:{type:Boolean,default:void 0},showClear:{type:Boolean,default:void 0},loading:Boolean,onClear:Function},setup(t,{slots:n}){return()=>{let{clsPrefix:r}=t;return e(),_(x,{clsPrefix:r,class:a(`${r}-base-suffix`),strokeWidth:24,scale:.85,show:t.loading},{default:()=>t.showArrow?(e(),_(T,{key:1,clsPrefix:r,show:t.showClear,onClear:t.onClear},{placeholder:()=>(e(),_(v,{clsPrefix:r,class:a(`${r}-base-suffix__arrow`)},{default:()=>d(n.default,()=>[(e(),_(E))])},1032,[`clsPrefix`,`class`]))},1032,[`clsPrefix`,`show`,`onClear`])):null},1032,[`clsPrefix`,`class`,`show`])}}});export{E as n,T as r,D as t};