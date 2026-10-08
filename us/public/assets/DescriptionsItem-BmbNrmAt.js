import{$t as e,C as t,Cn as n,Ft as r,It as i,K as a,Mt as o,Nt as s,Ot as c,Pt as l,R as u,V as d,ar as f,cn as p,en as m,fn as h,jt as g,kt as _,rn as v,w as y,yr as b}from"./is-browser-dQ-mLBB2.js";import{c as x}from"./client-BpOzf98Y.js";import{r as S}from"./cssr-DWJ_V8AC.js";import{t as C}from"./flatten-LMGZxwim.js";import{t as w}from"./get-slot-6kXJmSMP.js";import{x as T}from"./index-Cc8HQXPT.js";function E(e,t=`default`,n=[]){let{children:r}=e;if(typeof r==`object`&&r&&!Array.isArray(r)){let e=r[t];if(typeof e==`function`)return e()}return n}var D=c([_(`descriptions`,{fontSize:`var(--n-font-size)`},[_(`descriptions-separator`,`
 display: inline-block;
 margin: 0 8px 0 2px;
 `),_(`descriptions-table-wrapper`,[_(`descriptions-table`,[_(`descriptions-table-row`,[_(`descriptions-table-header`,{padding:`var(--n-th-padding)`}),_(`descriptions-table-content`,{padding:`var(--n-td-padding)`})])])]),s(`bordered`,[_(`descriptions-table-wrapper`,[_(`descriptions-table`,[_(`descriptions-table-row`,[c(`&:last-child`,[_(`descriptions-table-content`,{paddingBottom:0})])])])])]),o(`left-label-placement`,[_(`descriptions-table-content`,[c(`> *`,{verticalAlign:`top`})])]),o(`left-label-align`,[c(`th`,{textAlign:`left`})]),o(`center-label-align`,[c(`th`,{textAlign:`center`})]),o(`right-label-align`,[c(`th`,{textAlign:`right`})]),o(`bordered`,[_(`descriptions-table-wrapper`,`
 border-radius: var(--n-border-radius);
 overflow: hidden;
 background: var(--n-merged-td-color);
 border: 1px solid var(--n-merged-border-color);
 `,[_(`descriptions-table`,[_(`descriptions-table-row`,[c(`&:not(:last-child)`,[_(`descriptions-table-content`,{borderBottom:`1px solid var(--n-merged-border-color)`}),_(`descriptions-table-header`,{borderBottom:`1px solid var(--n-merged-border-color)`})]),_(`descriptions-table-header`,`
 font-weight: 400;
 background-clip: padding-box;
 background-color: var(--n-merged-th-color);
 `,[c(`&:not(:last-child)`,{borderRight:`1px solid var(--n-merged-border-color)`})]),_(`descriptions-table-content`,[c(`&:not(:last-child)`,{borderRight:`1px solid var(--n-merged-border-color)`})])])])])]),_(`descriptions-header`,`
 font-weight: var(--n-th-font-weight);
 font-size: 18px;
 transition: color .3s var(--n-bezier);
 line-height: var(--n-line-height);
 margin-bottom: 16px;
 color: var(--n-title-text-color);
 `),_(`descriptions-table-wrapper`,`
 transition:
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[_(`descriptions-table`,`
 width: 100%;
 border-collapse: separate;
 border-spacing: 0;
 box-sizing: border-box;
 `,[_(`descriptions-table-row`,`
 box-sizing: border-box;
 transition: border-color .3s var(--n-bezier);
 `,[_(`descriptions-table-header`,`
 font-weight: var(--n-th-font-weight);
 line-height: var(--n-line-height);
 display: table-cell;
 box-sizing: border-box;
 color: var(--n-th-text-color);
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `),_(`descriptions-table-content`,`
 vertical-align: top;
 line-height: var(--n-line-height);
 display: table-cell;
 box-sizing: border-box;
 color: var(--n-td-text-color);
 transition:
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `,[g(`content`,`
 transition: color .3s var(--n-bezier);
 display: inline-block;
 color: var(--n-td-text-color);
 `)]),g(`label`,`
 font-weight: var(--n-th-font-weight);
 transition: color .3s var(--n-bezier);
 display: inline-block;
 margin-right: 14px;
 color: var(--n-th-text-color);
 `)])])])]),_(`descriptions-table-wrapper`,`
 --n-merged-th-color: var(--n-th-color);
 --n-merged-td-color: var(--n-td-color);
 --n-merged-border-color: var(--n-border-color);
 `),r(_(`descriptions-table-wrapper`,`
 --n-merged-th-color: var(--n-th-color-modal);
 --n-merged-td-color: var(--n-td-color-modal);
 --n-merged-border-color: var(--n-border-color-modal);
 `)),i(_(`descriptions-table-wrapper`,`
 --n-merged-th-color: var(--n-th-color-popover);
 --n-merged-td-color: var(--n-td-color-popover);
 --n-merged-border-color: var(--n-border-color-popover);
 `))]),O=`DESCRIPTION_ITEM_FLAG`;function k(e){return typeof e==`object`&&e&&!Array.isArray(e)?e.type&&e.type.DESCRIPTION_ITEM_FLAG:!1}h(),f();var A=[`colspan`],j=[`colspan`],M=[`colspan`],N=[`colspan`],P={...t.props,title:String,column:{type:Number,default:3},columns:Number,labelPlacement:{type:String,default:`top`},labelAlign:{type:String,default:`left`},separator:{type:String,default:`:`},size:String,bordered:Boolean,labelClass:String,labelStyle:[Object,String],contentClass:String,contentStyle:[Object,String]},F=p({name:`Descriptions`,props:P,slots:Object,setup(n){let{mergedClsPrefixRef:r,inlineThemeDisabled:i,mergedComponentPropsRef:o}=a(n),s=e(()=>n.size||o?.value?.Descriptions?.size||`medium`),c=t(`Descriptions`,`-descriptions`,D,T,n,r),u=e(()=>{let{bordered:e}=n,t=s.value,{common:{cubicBezierEaseInOut:r},self:{titleTextColor:i,thColor:a,thColorModal:o,thColorPopover:u,thTextColor:d,thFontWeight:f,tdTextColor:p,tdColor:m,tdColorModal:h,tdColorPopover:g,borderColor:_,borderColorModal:v,borderColorPopover:y,borderRadius:b,lineHeight:x,[l(`fontSize`,t)]:S,[l(e?`thPaddingBordered`:`thPadding`,t)]:C,[l(e?`tdPaddingBordered`:`tdPadding`,t)]:w}}=c.value;return{"--n-title-text-color":i,"--n-th-padding":C,"--n-td-padding":w,"--n-font-size":S,"--n-bezier":r,"--n-th-font-weight":f,"--n-line-height":x,"--n-th-text-color":d,"--n-td-text-color":p,"--n-th-color":a,"--n-th-color-modal":o,"--n-th-color-popover":u,"--n-td-color":m,"--n-td-color-modal":h,"--n-td-color-popover":g,"--n-border-radius":b,"--n-border-color":_,"--n-border-color-modal":v,"--n-border-color-popover":y}}),d=i?y(`descriptions`,e(()=>{let e=``,{bordered:t}=n;return t&&(e+=`a`),e+=s.value[0],e}),u,n):void 0;return{mergedClsPrefix:r,cssVars:i?void 0:u,themeClass:d?.themeClass,onRender:d?.onRender,compitableColumn:S(n,[`columns`,`column`]),inlineThemeDisabled:i,mergedSize:s}},render(){let e=this.$slots.default,t=e?C(e()):[];t.length;let{contentClass:r,labelClass:i,compitableColumn:a,labelPlacement:o,labelAlign:s,mergedSize:c,bordered:l,title:f,cssVars:p,mergedClsPrefix:h,separator:g,onRender:_}=this;_?.();let y=t.filter(e=>k(e)),S=y.reduce((e,t,s)=>{let c=t.props||{},f=y.length-1===s,p=[`label`in c?c.label:E(t,`label`)],_=[E(t)],x=c.span||1,S=e.span;e.span+=x;let C=c.labelStyle||c[`label-style`]||this.labelStyle,w=c.contentStyle||c[`content-style`]||this.contentStyle;if(o===`left`)l?e.row.push((n(),v(`th`,{key:1,class:u([`${h}-descriptions-table-header`,i]),colspan:1,style:b(C)},[d(()=>p)],6)),(n(),v(`td`,{key:2,class:u([`${h}-descriptions-table-content`,r]),colspan:f?(a-S)*2+1:x*2-1,style:b(w)},[d(()=>_)],14,A))):e.row.push((n(),v(`td`,{key:3,class:u(`${h}-descriptions-table-content`),colspan:f?(a-S)*2:x*2},[m(`span`,{class:u([`${h}-descriptions-table-content__label`,i]),style:b(C)},[d(()=>[...p,g&&(n(),v(`span`,{key:4,class:u(`${h}-descriptions-separator`)},[d(()=>g)],2))])],6),m(`span`,{class:u([`${h}-descriptions-table-content__content`,r]),style:b(w)},[d(()=>_)],6)],10,j)));else{let t=f?(a-S)*2:x*2;e.row.push((n(),v(`th`,{key:5,class:u([`${h}-descriptions-table-header`,i]),colspan:t,style:b(C)},[d(()=>p)],14,M))),e.secondRow.push((n(),v(`td`,{key:6,class:u([`${h}-descriptions-table-content`,r]),colspan:t,style:b(w)},[d(()=>_)],14,N)))}return(e.span>=a||f)&&(e.span=0,e.row.length&&(e.rows.push(e.row),e.row=[]),o!==`left`&&e.secondRow.length&&(e.rows.push(e.secondRow),e.secondRow=[])),e},{span:0,row:[],secondRow:[],rows:[]}).rows.map(e=>(n(),v(`tr`,{class:u(`${h}-descriptions-table-row`)},[d(()=>e)],2)));return n(),v(`div`,{style:b(p),class:u([`${h}-descriptions`,this.themeClass,`${h}-descriptions--${o}-label-placement`,`${h}-descriptions--${s}-label-align`,`${h}-descriptions--${c}-size`,l&&`${h}-descriptions--bordered`])},[f||this.$slots.header?(n(),v(`div`,{key:0,class:u(`${h}-descriptions-header`)},[d(()=>f||w(this,`header`))],2)):d(()=>null),m(`div`,{class:u(`${h}-descriptions-table-wrapper`)},[m(`table`,{class:u(`${h}-descriptions-table`)},[m(`tbody`,null,[d(()=>o===`top`&&(n(),v(`tr`,{class:u(`${h}-descriptions-table-row`),style:{visibility:`collapse`}},[d(()=>x(a*2,(n(),v(`td`))))],2))),d(()=>S)])],2)],2)],6)}});h();var I={label:String,span:{type:Number,default:1},labelClass:String,labelStyle:[Object,String],contentClass:String,contentStyle:[Object,String]},L=p({name:`DescriptionsItem`,[O]:!0,props:I,slots:Object,render(){return null}});export{F as n,L as t};