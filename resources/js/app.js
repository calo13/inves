import './bootstrap';
// Livewire inicia Alpine; evitar una segunda instancia.
window.ioChart=async(el,rows)=>{
 const {default:ApexCharts}=await import('apexcharts');
 if(el.__chart)return;
 const chart=new ApexCharts(el,{chart:{type:'bar',height:280,toolbar:{show:false},fontFamily:'Outfit, sans-serif'},series:[{name:'Carga ponderada',data:rows.map(r=>r.carga)},{name:'Cantidad de tickets',data:rows.map(r=>r.tickets)}],colors:[getComputedStyle(document.documentElement).getPropertyValue('--color-brand-500').trim(),getComputedStyle(document.documentElement).getPropertyValue('--color-brand-200').trim()],plotOptions:{bar:{borderRadius:4,columnWidth:'42%'}},dataLabels:{enabled:false},xaxis:{categories:rows.map(r=>r.nombre),axisBorder:{show:false},axisTicks:{show:false}},grid:{borderColor:'#f2f4f7'},legend:{position:'top',horizontalAlign:'left'},yaxis:{min:0,labels:{formatter:v=>v.toFixed(0)}},tooltip:{y:{formatter:v=>v.toFixed(2)}}});el.__chart=chart;chart.render();
};
window.ioCalendar=async(el,events)=>{
 const {renderCalendar}=await import('./io-calendar');
 renderCalendar(el,events);
};
