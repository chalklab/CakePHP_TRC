Clazz.declarePackage ("J.modelkit");
Clazz.load (null, "J.modelkit.Constraint", ["java.lang.IllegalArgumentException", "JU.Measure", "$.P3", "$.V3"], function () {
c$ = Clazz.decorateAsClass (function () {
this.type = 0;
this.symop = null;
this.points = null;
this.offset = null;
this.plane = null;
this.unitVector = null;
this.value = 0;
Clazz.instantialize (this, arguments);
}, J.modelkit, "Constraint");
Clazz.makeConstructor (c$, 
function (type, params) {
this.type = type;
switch (type) {
case 6:
break;
case 4:
this.offset = params[0];
this.unitVector = JU.V3.newVsub (params[1], this.offset);
this.unitVector.normalize ();
break;
case 5:
this.plane = params[0];
break;
case 0:
this.symop = params[0];
this.points =  new Array (1);
this.offset = params[1];
break;
case 1:
this.value = (params[0]).doubleValue ();
this.points =  Clazz.newArray (-1, [params[1], null]);
break;
case 2:
this.value = (params[0]).doubleValue ();
this.points =  Clazz.newArray (-1, [params[1], params[2], null]);
break;
case 3:
this.value = (params[0]).doubleValue ();
this.points =  Clazz.newArray (-1, [params[1], params[2], params[3], null]);
break;
default:
throw  new IllegalArgumentException ();
}
}, "~N,~A");
Clazz.defineMethod (c$, "constrain", 
function (ptOld, ptNew) {
var v =  new JU.V3 ();
var p = JU.P3.newP (ptOld);
switch (this.type) {
case 6:
ptNew.x = NaN;
return;
case 4:
JU.Measure.projectOntoAxis (p, this.offset, this.unitVector, v);
if (p.distanceSquared (ptOld) > 1.96E-6) {
ptNew.x = NaN;
} else {
JU.Measure.projectOntoAxis (ptNew, this.offset, this.unitVector, v);
}break;
case 5:
if (Math.abs (JU.Measure.getPlaneProjection (p, this.plane, v, v)) > 0.01) {
ptNew.x = NaN;
} else {
JU.Measure.getPlaneProjection (ptNew, this.plane, v, v);
ptNew.setT (v);
}break;
}
}, "JU.P3,JU.P3");
Clazz.defineStatics (c$,
"TYPE_SYMMETRY", 0,
"TYPE_DISTANCE", 1,
"TYPE_ANGLE", 2,
"TYPE_DIHEDRAL", 3,
"TYPE_VECTOR", 4,
"TYPE_PLANE", 5,
"TYPE_LOCKED", 6);
});
