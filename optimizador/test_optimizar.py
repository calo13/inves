import unittest
from optimizar import solve

class SolverTest(unittest.TestCase):
    def data(self,n=2):
        return {'gestores':[{'id':1,'capacidad':1,'ausencias':[]},{'id':2,'capacidad':1,'ausencias':[]}],
                'tickets':[{'id':1,'peso':4,'desde':'2026-10-26','hasta':'2026-11-30','costos':{'1':0,'2':0}}],
                'sesiones':[{'id':i,'ticket_id':1,'fecha':'2026-11-02','critica':True} for i in range(1,n+1)],
                'parametros':{'theta':0.25,'w1':10,'w2':1,'semilla':2026,'tiempo_limite':2}}
    def test_support_and_daily_capacity(self):
        r=solve(self.data());self.assertEqual(r['errores'],[])
        self.assertEqual(len(r['supervisiones']),2)
        self.assertEqual(len({(s['gestor_id'],s['fecha']) for s in r['supervisiones']}),2)
    def test_infeasible_is_reported(self):
        self.assertTrue(solve(self.data(3))['errores'])
    def test_absence(self):
        d=self.data(1);d['gestores'][0]['ausencias']=[{'desde':'2026-11-01','hasta':'2026-11-30'}]
        r=solve(d);self.assertEqual(r['asignaciones'][1],2);self.assertEqual(r['supervisiones'][0]['gestor_id'],2)
    def test_regular_minimum_and_fixed_load(self):
        d=self.data(4);d['cargas_fijas']={'1':10,'2':0}
        for s in d['sesiones']:s['critica']=False
        r=solve(d);self.assertEqual(len(r['supervisiones']),1);self.assertEqual(r['asignaciones'][1],2)

if __name__=='__main__':unittest.main()
