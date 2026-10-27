"""CP-SAT: recibe un JSON por stdin y emite solo JSON por stdout."""
import json, sys, math, hashlib
from ortools.sat.python import cp_model

def solve(data):
    model=cp_model.CpModel(); gs=data['gestores']; ts=data['tickets']; ss=data['sesiones']; p=data['parametros']
    if not gs:
        return {'motor':'OR-Tools CP-SAT','asignaciones':{},'supervisiones':[],'errores':['No hay gestores activos.']}
    def available(g,a,b):
        return all(not(a<=v['hasta'] and b>=v['desde']) for v in g['ausencias'])
    x={(t['id'],g['id']):model.new_bool_var(f"x_{t['id']}_{g['id']}") for t in ts for g in gs}
    for t in ts:
        model.add(sum(x[t['id'],g['id']] for g in gs)==1)
        for g in gs:
            if not available(g,t['desde'],t['hasta']):model.add(x[t['id'],g['id']]==0)
    weights={t['id']:round(t['peso']*100) for t in ts}
    ceiling=max(1,(sum(weights.values())+round(sum(data.get('cargas_fijas',{}).values())*100))*100)
    loads=[]
    for g in gs:
        cap=max(1,round(g['capacidad']*100))
        load=model.new_int_var(0,ceiling,f"load_{g['id']}")
        raw=sum(weights[t['id']]*x[t['id'],g['id']] for t in ts)+round(data.get('cargas_fijas',{}).get(str(g['id']),0)*100)
        model.add_division_equality(load,raw*100,cap);loads.append(load)
    high=model.new_int_var(0,ceiling,'high');low=model.new_int_var(0,ceiling,'low')
    model.add_max_equality(high,loads);model.add_min_equality(low,loads)
    y={(s['id'],g['id']):model.new_bool_var(f"y_{s['id']}_{g['id']}") for s in ss for g in gs}
    for s in ss:
        total=sum(y[s['id'],g['id']] for g in gs)
        if s['critica']:model.add(total==1)
        else:model.add(total<=1)
        for g in gs:
            if not available(g,s['fecha'],s['fecha']):model.add(y[s['id'],g['id']]==0)
    for date in set(s['fecha'] for s in ss):
        for g in gs:model.add(sum(y[s['id'],g['id']] for s in ss if s['fecha']==date)<=1)
    regs=[s for s in ss if not s['critica']];minimum=math.ceil(p['theta']*len(regs))
    model.add(sum(y[s['id'],g['id']] for s in regs for g in gs)==minimum)
    # Favorecer cubrir tickets distintos. El desempate por hash hace reproducible el muestreo.
    coverage=[]
    for tid in set(s['ticket_id'] for s in regs):
        v=model.new_bool_var(f'covered_{tid}');model.add_max_equality(v,[y[s['id'],g['id']] for s in regs if s['ticket_id']==tid for g in gs]);coverage.append(v)
    cost=sum(round((t['costos'][str(g['id'])] if str(g['id']) in t['costos'] else t['costos'][g['id']])*100)*x[t['id'],g['id']] for t in ts for g in gs)
    # Resolver primero el objetivo de carga/urgencia. Desempatar muestreo sin degradar ese objetivo.
    primary=round(p['w1']*100)*(high-low)+round(p['w2']*100)*cost
    inc=data.get('incumbente',{})
    inc_assign=inc.get('asignaciones',{})
    if not inc.get('errores') and all(str(t['id']) in inc_assign for t in ts):
        for (tid,gid),var in x.items():model.add_hint(var,int(inc_assign[str(tid)]==gid))
        inc_sup={(v['sesion_id'],v['gestor_id']) for v in inc.get('supervisiones',[])}
        for key,var in y.items():model.add_hint(var,int(key in inc_sup))
        inc_loads=[]
        for g in gs:
            raw=sum(weights[t['id']] for t in ts if inc_assign[str(t['id'])]==g['id'])+round(data.get('cargas_fijas',{}).get(str(g['id']),0)*100)
            inc_loads.append(raw*100//max(1,round(g['capacidad']*100)))
        inc_cost=sum(round(t['costos'][str(inc_assign[str(t['id'])])]*100) for t in ts)
        bound=round(p['w1']*100)*(max(inc_loads)-min(inc_loads))+round(p['w2']*100)*inc_cost
        model.add(primary<=bound)
    model.minimize(primary)
    solver=cp_model.CpSolver();solver.parameters.max_time_in_seconds=p['tiempo_limite']/2;solver.parameters.random_seed=int(p['semilla']);solver.parameters.num_search_workers=1
    status=solver.solve(model)
    if status not in (cp_model.OPTIMAL,cp_model.FEASIBLE):return {'motor':'OR-Tools CP-SAT','asignaciones':{},'supervisiones':[],'errores':[f'Sin solución certificada: {solver.status_name(status)}.']}
    initial_status=solver.status_name(status)
    best=round(solver.objective_value);model.add(primary<=best)
    model.clear_hints()
    for var in list(x.values())+list(y.values()):model.add_hint(var,solver.value(var))
    supports=[]
    for s in ss:
        for g in gs:
            v=model.new_bool_var(f"support_{s['id']}_{g['id']}");model.add(v>=y[s['id'],g['id']]-x[s['ticket_id'],g['id']]);supports.append(v)
    randomcost=sum((int(hashlib.sha256(f"{p['semilla']}|{s['id']}".encode()).hexdigest()[:8],16)%100)*y[s['id'],g['id']] for s in regs for g in gs)
    model.minimize(randomcost+100*sum(supports)-10000*sum(coverage))
    saved_assign={t['id']:next(g['id'] for g in gs if solver.value(x[t['id'],g['id']])) for t in ts}
    saved_super=[(s,g) for s in ss for g in gs if solver.value(y[s['id'],g['id']])]
    second=solver.solve(model)
    if second in (cp_model.OPTIMAL,cp_model.FEASIBLE):
        saved_assign={t['id']:next(g['id'] for g in gs if solver.value(x[t['id'],g['id']])) for t in ts}
        saved_super=[(s,g) for s in ss for g in gs if solver.value(y[s['id'],g['id']])]
    result=[{'sesion_id':s['id'],'gestor_id':g['id'],'fecha':s['fecha'],'tipo':'apoyo' if saved_assign[s['ticket_id']]!=g['id'] else ('obligatoria' if s['critica'] else 'muestreo')} for s,g in saved_super]
    return {'motor':'OR-Tools CP-SAT','asignaciones':saved_assign,'supervisiones':result,'errores':[],'estado_solver':solver.status_name(second) if second in (cp_model.OPTIMAL,cp_model.FEASIBLE) else initial_status,'desempate_solver':solver.status_name(second)}
if __name__=='__main__':
    print(json.dumps(solve(json.load(sys.stdin)),ensure_ascii=False))
