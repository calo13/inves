import { Calendar } from 'fullcalendar';
import dayGridPlugin from 'fullcalendar/daygrid';
import timeGridPlugin from 'fullcalendar/timegrid';
import themePlugin from 'fullcalendar/themes/classic';
import esLocale from 'fullcalendar/locales/es';
import 'fullcalendar/skeleton.css';
import 'fullcalendar/themes/classic/palette.css';
import 'fullcalendar/themes/classic/theme.css';
export function renderCalendar(el,events){
 if(el.__calendar)return;
 const tokens=getComputedStyle(document.documentElement);
 const prepared=events.map(e=>{const cls=e.classNames?.[0];const token=cls==='io-event-support'?'--color-warning-500':(cls==='io-event-sample'?'--color-success-500':'--color-brand-500');return {...e,className:(e.classNames||[]).join(' '),color:tokens.getPropertyValue(token).trim(),contrastColor:cls==='io-event-support'?'#101828':'#ffffff'};});
 const calendar=new Calendar(el,{plugins:[themePlugin,dayGridPlugin,timeGridPlugin],initialView:'dayGridMonth',initialDate:events[0]?.start||new Date(),events:prepared,locales:[esLocale],locale:'es',firstDay:1,height:'auto',headerToolbar:{start:'prev,next today',center:'title',end:'dayGridMonth,timeGridWeek'},buttons:{today:{text:'Hoy'},dayGridMonth:{text:'Mes'},timeGridWeek:{text:'Semana'}},dayHeaderFormat:{weekday:'short'},eventDisplay:'block'});el.__calendar=calendar;calendar.render();
}
