import{_ as a,o as i,c,a as l,b as u}from"./js/_plugin-vue_export-helper.2d9794a3.js";const p={data(){return{display:!1,interval:null}},methods:{addMenuHighlight(){const t=document.querySelector("#toplevel_page_aioseo");if(!t)return;t.querySelectorAll(".wp-submenu li").forEach(e=>{const o=e.querySelector("a");if(!o)return;const n=o.querySelector(".aioseo-menu-highlight");if(n){e.classList.add("aioseo-submenu-highlight"),n.classList.contains("red")&&e.classList.add("red");const s=e.querySelector("a");s&&!n.classList.contains("red")&&s.setAttribute("target","_blank")}})}},created(){this.addMenuHighlight()}},d={key:0};function _(t,r,e,o,n,s){return n.display?(i(),c("div",d)):l("",!0)}const m=a(p,[["render",_]]);document.getElementById("aioseo-admin")&&u(m).mount("#aioseo-admin");