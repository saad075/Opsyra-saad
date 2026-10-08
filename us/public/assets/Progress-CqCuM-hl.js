import{$t as e,B as t,C as n,Cn as r,K as i,Mt as a,Ot as o,Pt as s,R as c,Rt as l,V as u,Yt as d,ar as f,cn as p,en as m,fn as h,kt as g,rn as _,tn as v,w as y,x as b,yr as x}from"./is-browser-dQ-mLBB2.js";import{t as S}from"./format-length-BwSo_EUP.js";import{G as C,K as w,W as T,d as E,q as D}from"./index-Cc8HQXPT.js";h(),f();var O=[`id`],k=[`stop-color`],A=[`stop-color`],j=[`viewBox`],M=[`d`,`stroke-width`],N=[`d`,`stroke-width`],P={success:(r(),v(C)),error:(r(),v(D)),warning:(r(),v(T)),info:(r(),v(w))},F=p({name:`ProgressCircle`,props:{clsPrefix:{type:String,required:!0},status:{type:String,required:!0},strokeWidth:{type:Number,required:!0},fillColor:[String,Object],railColor:String,railStyle:[String,Object],percentage:{type:Number,default:0},offsetDegree:{type:Number,default:0},showIndicator:{type:Boolean,required:!0},indicatorTextColor:String,unit:String,viewBoxWidth:{type:Number,required:!0},gapDegree:{type:Number,required:!0},gapOffsetDegree:{type:Number,default:0}},setup(t,{slots:n}){let i=e(()=>{let e=`gradient`,{fillColor:n}=t;return typeof n==`object`?`${e}-${l(JSON.stringify(n))}`:e});function a(e,n,r,a){let{gapDegree:o,viewBoxWidth:s,strokeWidth:c}=t,l=50+c/2,u=`M ${l},${l} m 0,50
      a 50,50 0 1 1 0,-100
      a 50,50 0 1 1 0,100`,d=Math.PI*2*50;return{pathString:u,pathStyle:{stroke:a===`rail`?r:typeof t.fillColor==`object`?`url(#${i.value})`:r,strokeDasharray:`${Math.min(e,100)/100*(d-o)}px ${s*8}px`,strokeDashoffset:`-${o/2}px`,transformOrigin:n?`center`:void 0,transform:n?`rotate(${n}deg)`:void 0}}}let o=()=>{let e=typeof t.fillColor==`object`,n=e?t.fillColor.stops[0]:``,a=e?t.fillColor.stops[1]:``;return e&&(r(),_(`defs`,null,[m(`linearGradient`,{id:i.value,x1:`0%`,y1:`100%`,x2:`100%`,y2:`0%`},[m(`stop`,{offset:`0%`,"stop-color":n},null,8,k),m(`stop`,{offset:`100%`,"stop-color":a},null,8,A)],8,O)]))};return()=>{let{fillColor:e,railColor:i,strokeWidth:s,offsetDegree:l,status:f,percentage:p,showIndicator:h,indicatorTextColor:g,unit:y,gapOffsetDegree:S,clsPrefix:C}=t,{pathString:w,pathStyle:T}=a(100,0,i,`rail`),{pathString:E,pathStyle:D}=a(p,l,e,`fill`),O=100+s;return r(),_(`div`,{class:c(`${C}-progress-content`),role:`none`},[m(`div`,{class:c(`${C}-progress-graph`),"aria-hidden":!0},[m(`div`,{class:c(`${C}-progress-graph-circle`),style:x({transform:S?`rotate(${S}deg)`:void 0})},[(r(),_(`svg`,{viewBox:`0 0 ${O} ${O}`},[u(()=>o()),m(`g`,null,[m(`path`,{class:c(`${C}-progress-graph-circle-rail`),d:w,"stroke-width":s,"stroke-linecap":`round`,fill:`none`,style:x(T)},null,14,M)]),m(`g`,null,[m(`path`,{class:c([`${C}-progress-graph-circle-fill`,p===0&&`${C}-progress-graph-circle-fill--empty`]),d:E,"stroke-width":s,"stroke-linecap":`round`,fill:`none`,style:x(D)},null,14,N)])],8,j))],6)],2),h?(r(),_(`div`,{key:0},[n.default?(r(),_(`div`,{key:0,class:c(`${C}-progress-custom-content`),role:`none`},[u(()=>n.default())],2)):(r(),_(d,{key:1},[f==="default"?(r(),_(`div`,{key:1,class:c(`${C}-progress-text`),style:x({color:g}),role:`none`},[m(`span`,{class:c(`${C}-progress-text__percentage`)},[u(()=>p)],2),m(`span`,{class:c(`${C}-progress-text__unit`)},[u(()=>y)],2)],6)):(r(),_(`div`,{key:0,class:c(`${C}-progress-icon`),"aria-hidden":!0},[(r(),v(b,{clsPrefix:C},{default:()=>P[f]},1032,[`clsPrefix`]))],2))],64))])):u(()=>null)],2)}}});h(),f();var I={success:(r(),v(C)),error:(r(),v(D)),warning:(r(),v(T)),info:(r(),v(w))},L=p({name:`ProgressLine`,props:{clsPrefix:{type:String,required:!0},percentage:{type:Number,default:0},railColor:String,railStyle:[String,Object],fillColor:[String,Object],status:{type:String,required:!0},indicatorPlacement:{type:String,required:!0},indicatorTextColor:String,unit:{type:String,default:`%`},processing:{type:Boolean,required:!0},showIndicator:{type:Boolean,required:!0},height:[String,Number],railBorderRadius:[String,Number],fillBorderRadius:[String,Number]},setup(t,{slots:n}){let i=e(()=>S(t.height)),a=e(()=>typeof t.fillColor==`object`?`linear-gradient(to right, ${t.fillColor?.stops[0]} , ${t.fillColor?.stops[1]})`:t.fillColor),o=e(()=>t.railBorderRadius===void 0?t.height===void 0?``:S(t.height,{c:.5}):S(t.railBorderRadius)),s=e(()=>t.fillBorderRadius===void 0?t.railBorderRadius===void 0?t.height===void 0?``:S(t.height,{c:.5}):S(t.railBorderRadius):S(t.fillBorderRadius));return()=>{let{indicatorPlacement:e,railColor:l,railStyle:f,percentage:p,unit:h,indicatorTextColor:g,status:y,showIndicator:S,processing:C,clsPrefix:w}=t;return r(),_(`div`,{class:c(`${w}-progress-content`),role:`none`},[m(`div`,{class:c(`${w}-progress-graph`),"aria-hidden":!0},[m(`div`,{class:c([`${w}-progress-graph-line`,{[`${w}-progress-graph-line--indicator-${e}`]:!0}])},[m(`div`,{class:c(`${w}-progress-graph-line-rail`),style:x([{backgroundColor:l,height:i.value,borderRadius:o.value},f])},[m(`div`,{class:c([`${w}-progress-graph-line-fill`,C&&`${w}-progress-graph-line-fill--processing`]),style:x({maxWidth:`${t.percentage}%`,background:a.value,height:i.value,lineHeight:i.value,borderRadius:s.value})},[e===`inside`?(r(),_(`div`,{key:0,class:c(`${w}-progress-graph-line-indicator`),style:x({color:g})},[n.default?(r(),_(d,{key:0},[u(()=>n.default())],64)):(r(),_(d,{key:1},[u(()=>`${p}${h}`)],64))],6)):u(()=>null)],6)],6)],2)],2),S&&e===`outside`?(r(),_(`div`,{key:0},[n.default?(r(),_(`div`,{key:0,class:c(`${w}-progress-custom-content`),style:x({color:g}),role:`none`},[u(()=>n.default())],6)):(r(),_(d,{key:1},[y==="default"?(r(),_(`div`,{key:0,role:`none`,class:c(`${w}-progress-icon ${w}-progress-icon--as-text`),style:x({color:g})},[u(()=>p),u(()=>h)],6)):(r(),_(`div`,{key:1,class:c(`${w}-progress-icon`),"aria-hidden":!0},[(r(),v(b,{clsPrefix:w},{default:()=>I[y]},1032,[`clsPrefix`]))],2))],64))])):u(()=>null)],2)}}});h(),f();var R=[`id`],z=[`stop-color`],B=[`stop-color`],V=[`d`,`stroke-width`],H=[`d`,`stroke-width`],U=[`viewBox`];function W(e,t,n=100){return`m ${n/2} ${n/2-e} a ${e} ${e} 0 1 1 0 ${2*e} a ${e} ${e} 0 1 1 0 -${2*e}`}var G=p({name:`ProgressMultipleCircle`,props:{clsPrefix:{type:String,required:!0},viewBoxWidth:{type:Number,required:!0},percentage:{type:Array,default:[0]},strokeWidth:{type:Number,required:!0},circleGap:{type:Number,required:!0},showIndicator:{type:Boolean,required:!0},fillColor:{type:Array,default:()=>[]},railColor:{type:Array,default:()=>[]},railStyle:{type:Array,default:()=>[]}},setup(t,{slots:n}){let i=e(()=>t.percentage.map((e,n)=>`${Math.PI*e/100*(t.viewBoxWidth/2-t.strokeWidth/2*(1+2*n)-t.circleGap*n)*2}, ${t.viewBoxWidth*8}`)),a=(e,n)=>{let i=t.fillColor[n],a=typeof i==`object`?i.stops[0]:``,o=typeof i==`object`?i.stops[1]:``;return typeof t.fillColor[n]==`object`&&(r(),_(`linearGradient`,{id:`gradient-${n}`,x1:`100%`,y1:`0%`,x2:`0%`,y2:`100%`},[m(`stop`,{offset:`0%`,"stop-color":a},null,8,z),m(`stop`,{offset:`100%`,"stop-color":o},null,8,B)],8,R))};return()=>{let{viewBoxWidth:e,strokeWidth:o,circleGap:s,showIndicator:l,fillColor:d,railColor:f,railStyle:p,percentage:h,clsPrefix:g}=t;return r(),_(`div`,{class:c(`${g}-progress-content`),role:`none`},[m(`div`,{class:c(`${g}-progress-graph`),"aria-hidden":!0},[m(`div`,{class:c(`${g}-progress-graph-circle`)},[(r(),_(`svg`,{viewBox:`0 0 ${e} ${e}`},[m(`defs`,null,[u(()=>h.map((e,t)=>a(e,t)))]),u(()=>h.map((t,n)=>(r(),_(`g`,{key:n},[m(`path`,{class:c(`${g}-progress-graph-circle-rail`),d:W(e/2-o/2*(1+2*n)-s*n,o,e),"stroke-width":o,"stroke-linecap":`round`,fill:`none`,style:x([{strokeDashoffset:0,stroke:f[n]},p[n]])},null,14,V),m(`path`,{class:c([`${g}-progress-graph-circle-fill`,t===0&&`${g}-progress-graph-circle-fill--empty`]),d:W(e/2-o/2*(1+2*n)-s*n,o,e),"stroke-width":o,"stroke-linecap":`round`,fill:`none`,style:x({strokeDasharray:i.value[n],strokeDashoffset:0,stroke:typeof d[n]==`object`?`url(#gradient-${n})`:d[n]})},null,14,H)]))))],8,U))],2)],2),l&&n.default?(r(),_(`div`,{key:0},[m(`div`,{class:c(`${g}-progress-text`)},[u(()=>n.default())],2)])):u(()=>null)],2)}}}),K=o([g(`progress`,{display:`inline-block`},[g(`progress-icon`,`
 color: var(--n-icon-color);
 transition: color .3s var(--n-bezier);
 `),a(`line`,`
 width: 100%;
 display: block;
 `,[g(`progress-content`,`
 display: flex;
 align-items: center;
 `,[g(`progress-graph`,{flex:1})]),g(`progress-custom-content`,{marginLeft:`14px`}),g(`progress-icon`,`
 width: 30px;
 padding-left: 14px;
 height: var(--n-icon-size-line);
 line-height: var(--n-icon-size-line);
 font-size: var(--n-icon-size-line);
 `,[a(`as-text`,`
 color: var(--n-text-color-line-outer);
 text-align: center;
 width: 40px;
 font-size: var(--n-font-size);
 padding-left: 4px;
 transition: color .3s var(--n-bezier);
 `)])]),a(`circle, dashboard`,{width:`120px`},[g(`progress-custom-content`,`
 position: absolute;
 left: 50%;
 top: 50%;
 transform: translateX(-50%) translateY(-50%);
 display: flex;
 align-items: center;
 justify-content: center;
 `),g(`progress-text`,`
 position: absolute;
 left: 50%;
 top: 50%;
 transform: translateX(-50%) translateY(-50%);
 display: flex;
 align-items: center;
 color: inherit;
 font-size: var(--n-font-size-circle);
 color: var(--n-text-color-circle);
 font-weight: var(--n-font-weight-circle);
 transition: color .3s var(--n-bezier);
 white-space: nowrap;
 `),g(`progress-icon`,`
 position: absolute;
 left: 50%;
 top: 50%;
 transform: translateX(-50%) translateY(-50%);
 display: flex;
 align-items: center;
 color: var(--n-icon-color);
 font-size: var(--n-icon-size-circle);
 `)]),a(`multiple-circle`,`
 width: 200px;
 color: inherit;
 `,[g(`progress-text`,`
 font-weight: var(--n-font-weight-circle);
 color: var(--n-text-color-circle);
 position: absolute;
 left: 50%;
 top: 50%;
 transform: translateX(-50%) translateY(-50%);
 display: flex;
 align-items: center;
 justify-content: center;
 transition: color .3s var(--n-bezier);
 `)]),g(`progress-content`,{position:`relative`}),g(`progress-graph`,{position:`relative`},[g(`progress-graph-circle`,[o(`svg`,{verticalAlign:`bottom`}),g(`progress-graph-circle-fill`,`
 stroke: var(--n-fill-color);
 transition:
 opacity .3s var(--n-bezier),
 stroke .3s var(--n-bezier),
 stroke-dasharray .3s var(--n-bezier);
 `,[a(`empty`,{opacity:0})]),g(`progress-graph-circle-rail`,`
 transition: stroke .3s var(--n-bezier);
 overflow: hidden;
 stroke: var(--n-rail-color);
 `)]),g(`progress-graph-line`,[a(`indicator-inside`,[g(`progress-graph-line-rail`,`
 height: 16px;
 line-height: 16px;
 border-radius: 10px;
 `,[g(`progress-graph-line-fill`,`
 height: inherit;
 border-radius: 10px;
 `),g(`progress-graph-line-indicator`,`
 background: #0000;
 white-space: nowrap;
 text-align: right;
 margin-left: 14px;
 margin-right: 14px;
 height: inherit;
 font-size: 12px;
 color: var(--n-text-color-line-inner);
 transition: color .3s var(--n-bezier);
 `)])]),a(`indicator-inside-label`,`
 height: 16px;
 display: flex;
 align-items: center;
 `,[g(`progress-graph-line-rail`,`
 flex: 1;
 transition: background-color .3s var(--n-bezier);
 `),g(`progress-graph-line-indicator`,`
 background: var(--n-fill-color);
 font-size: 12px;
 transform: translateZ(0);
 display: flex;
 vertical-align: middle;
 height: 16px;
 line-height: 16px;
 padding: 0 10px;
 border-radius: 10px;
 position: absolute;
 white-space: nowrap;
 color: var(--n-text-color-line-inner);
 transition:
 right .2s var(--n-bezier),
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 `)]),g(`progress-graph-line-rail`,`
 position: relative;
 overflow: hidden;
 height: var(--n-rail-height);
 border-radius: 5px;
 background-color: var(--n-rail-color);
 transition: background-color .3s var(--n-bezier);
 `,[g(`progress-graph-line-fill`,`
 background: var(--n-fill-color);
 position: relative;
 border-radius: 5px;
 height: inherit;
 width: 100%;
 max-width: 0%;
 transition:
 background-color .3s var(--n-bezier),
 max-width .2s var(--n-bezier);
 `,[a(`processing`,[o(`&::after`,`
 content: "";
 background-image: var(--n-line-bg-processing);
 animation: progress-processing-animation 2s var(--n-bezier) infinite;
 `)])])])])])]),o(`@keyframes progress-processing-animation`,`
 0% {
 position: absolute;
 left: 0;
 top: 0;
 bottom: 0;
 right: 100%;
 opacity: 1;
 }
 66% {
 position: absolute;
 left: 0;
 top: 0;
 bottom: 0;
 right: 0;
 opacity: 0;
 }
 100% {
 position: absolute;
 left: 0;
 top: 0;
 bottom: 0;
 right: 0;
 opacity: 0;
 }
 `)]);h(),f();var q=[`aria-valuenow`,`role`],J={...n.props,processing:Boolean,type:{type:String,default:`line`},gapDegree:Number,gapOffsetDegree:Number,status:{type:String,default:`default`},railColor:[String,Array],railStyle:[String,Array],color:[String,Array,Object],viewBoxWidth:{type:Number,default:100},strokeWidth:{type:Number,default:7},percentage:[Number,Array],unit:{type:String,default:`%`},showIndicator:{type:Boolean,default:!0},indicatorPosition:{type:String,default:`outside`},indicatorPlacement:{type:String,default:`outside`},indicatorTextColor:String,circleGap:{type:Number,default:1},height:Number,borderRadius:[String,Number],fillBorderRadius:[String,Number],offsetDegree:Number},Y=p({name:`Progress`,props:J,setup(t){let r=e(()=>t.indicatorPlacement||t.indicatorPosition),a=e(()=>{if(t.gapDegree||t.gapDegree===0)return t.gapDegree;if(t.type===`dashboard`)return 75}),{mergedClsPrefixRef:o,inlineThemeDisabled:c}=i(t),l=n(`Progress`,`-progress`,K,E,t,o),u=e(()=>{let{status:e}=t,{common:{cubicBezierEaseInOut:n},self:{fontSize:r,fontSizeCircle:i,railColor:a,railHeight:o,iconSizeCircle:c,iconSizeLine:u,textColorCircle:d,textColorLineInner:f,textColorLineOuter:p,lineBgProcessing:m,fontWeightCircle:h,[s(`iconColor`,e)]:g,[s(`fillColor`,e)]:_}}=l.value;return{"--n-bezier":n,"--n-fill-color":_,"--n-font-size":r,"--n-font-size-circle":i,"--n-font-weight-circle":h,"--n-icon-color":g,"--n-icon-size-circle":c,"--n-icon-size-line":u,"--n-line-bg-processing":m,"--n-rail-color":a,"--n-rail-height":o,"--n-text-color-circle":d,"--n-text-color-line-inner":f,"--n-text-color-line-outer":p}}),d=c?y(`progress`,e(()=>t.status[0]),u,t):void 0;return{mergedClsPrefix:o,mergedIndicatorPlacement:r,gapDeg:a,cssVars:c?void 0:u,themeClass:d?.themeClass,onRender:d?.onRender}},render(){let{type:e,cssVars:n,indicatorTextColor:i,showIndicator:a,status:o,railColor:s,railStyle:l,color:f,percentage:p,viewBoxWidth:m,strokeWidth:h,mergedIndicatorPlacement:g,unit:y,borderRadius:b,fillBorderRadius:S,height:C,processing:w,circleGap:T,mergedClsPrefix:E,gapDeg:D,gapOffsetDegree:O,themeClass:k,$slots:A,onRender:j}=this;return j?.(),r(),_(`div`,{class:c([k,`${E}-progress`,`${E}-progress--${e}`,`${E}-progress--${o}`]),style:x(n),"aria-valuemax":100,"aria-valuemin":0,"aria-valuenow":p,role:e===`circle`||e===`line`||e===`dashboard`?`progressbar`:`none`},[e===`circle`||e===`dashboard`?(r(),v(F,{key:0,clsPrefix:E,status:o,showIndicator:a,indicatorTextColor:i,railColor:s,fillColor:f,railStyle:l,offsetDegree:this.offsetDegree,percentage:p,viewBoxWidth:m,strokeWidth:h,gapDegree:D===void 0?e===`dashboard`?75:0:D,gapOffsetDegree:O,unit:y},t(A),1032,[`clsPrefix`,`status`,`showIndicator`,`indicatorTextColor`,`railColor`,`fillColor`,`railStyle`,`offsetDegree`,`percentage`,`viewBoxWidth`,`strokeWidth`,`gapDegree`,`gapOffsetDegree`,`unit`])):(r(),_(d,{key:1},[e===`line`?(r(),v(L,{key:0,clsPrefix:E,status:o,showIndicator:a,indicatorTextColor:i,railColor:s,fillColor:f,railStyle:l,percentage:p,processing:w,indicatorPlacement:g,unit:y,fillBorderRadius:S,railBorderRadius:b,height:C},t(A),1032,[`clsPrefix`,`status`,`showIndicator`,`indicatorTextColor`,`railColor`,`fillColor`,`railStyle`,`percentage`,`processing`,`indicatorPlacement`,`unit`,`fillBorderRadius`,`railBorderRadius`,`height`])):(r(),_(d,{key:1},[e===`multiple-circle`?(r(),v(G,{key:0,clsPrefix:E,strokeWidth:h,railColor:s,fillColor:f,railStyle:l,viewBoxWidth:m,percentage:p,showIndicator:a,circleGap:T},t(A),1032,[`clsPrefix`,`strokeWidth`,`railColor`,`fillColor`,`railStyle`,`viewBoxWidth`,`percentage`,`showIndicator`,`circleGap`])):u(()=>null)],64))],64))],14,q)}});export{Y as t};