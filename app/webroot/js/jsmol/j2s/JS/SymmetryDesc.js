Clazz.declarePackage ("JS");
Clazz.load (["JU.P3", "$.V3"], "JS.SymmetryDesc", ["java.lang.Float", "java.util.Hashtable", "JU.BS", "$.Lst", "$.M4", "$.Measure", "$.P4", "$.PT", "$.Quat", "$.SB", "JM.Atom", "JS.T", "JS.SpaceGroup", "$.Symmetry", "$.SymmetryOperation", "JU.Escape", "$.Logger"], function () {
c$ = Clazz.decorateAsClass (function () {
this.modelSet = null;
this.vtemp = null;
this.ptemp = null;
this.ptemp2 = null;
this.pta01 = null;
this.pta02 = null;
this.vtrans = null;
Clazz.instantialize (this, arguments);
}, JS, "SymmetryDesc");
Clazz.prepareFields (c$, function () {
this.vtemp =  new JU.V3 ();
this.ptemp =  new JU.P3 ();
this.ptemp2 =  new JU.P3 ();
this.pta01 =  new JU.P3 ();
this.pta02 =  new JU.P3 ();
this.vtrans =  new JU.V3 ();
});
Clazz.makeConstructor (c$, 
function () {
});
Clazz.defineMethod (c$, "set", 
function (modelSet) {
this.modelSet = modelSet;
return this;
}, "JM.ModelSet");
c$.getType = Clazz.defineMethod (c$, "getType", 
 function (id) {
var type;
if (id == null) return 1073742001;
if (id.equalsIgnoreCase ("matrix")) return 12;
if (id.equalsIgnoreCase ("description")) return 1825200146;
if (id.equalsIgnoreCase ("axispoint")) return 134217751;
if (id.equalsIgnoreCase ("time")) return 268435633;
if (id.equalsIgnoreCase ("info")) return 1275068418;
if (id.equalsIgnoreCase ("element")) return 1086326789;
if (id.equalsIgnoreCase ("invariant")) return 36868;
type = JS.T.getTokFromName (id);
if (type != 0) return type;
type = JS.SymmetryDesc.getKeyType (id);
return (type < 0 ? type : 1073742327);
}, "~S");
c$.getKeyType = Clazz.defineMethod (c$, "getKeyType", 
 function (id) {
if ("type".equals (id)) id = "_type";
for (var type = 0; type < JS.SymmetryDesc.keys.length; type++) if (id.equalsIgnoreCase (JS.SymmetryDesc.keys[type])) return -1 - type;

return 0;
}, "~S");
c$.nullReturn = Clazz.defineMethod (c$, "nullReturn", 
 function (type) {
switch (type) {
case 135176:
return "draw ID sym_* delete";
case 1073741961:
case 1825200146:
case 1073741974:
case 1145047050:
case 11:
case 1073742078:
return "";
case 1140850689:
return  new JU.BS ();
default:
return null;
}
}, "~N");
c$.getInfo = Clazz.defineMethod (c$, "getInfo", 
 function (io, type) {
if (io.length == 0) return "";
if (type < 0 && -type <= JS.SymmetryDesc.keys.length && -type <= io.length) return io[-1 - type];
switch (type) {
case 1073742327:
case 1073741982:
return io;
case 1275068418:
var lst =  new java.util.Hashtable ();
for (var j = 0, n = io.length; j < n; j++) {
var key = (j == 3 ? "draw" : j == 7 ? "axispoint" : JS.SymmetryDesc.keys[j]);
if (io[j] != null) lst.put (key, io[j]);
}
return lst;
case 1073741961:
return io[0] + "  \t" + io[2];
case 1145047050:
return io[0];
case 1073742078:
return io[1];
default:
case 1825200146:
return io[2];
case 135176:
return io[3] + "\nprint " + JU.PT.esc (io[0] + " " + io[2]);
case 1145047051:
return io[4];
case 1073742178:
return io[5];
case 12289:
return io[6];
case 134217751:
return io[7];
case 1073741854:
return io[8];
case 134217729:
return io[9];
case 12:
return io[10];
case 1814695966:
return io[11];
case 4160:
return io[12];
case 268435633:
return io[13];
case 134217750:
return io[14];
case 1140850696:
return io[15];
case 1073741974:
return io[16];
case 1086326789:
return  Clazz.newArray (-1, [io[6], io[7], io[8], io[14], io[5]]);
case 36868:
return (io[5] != null ? "none" : io[6] != null ? io[6] : io[8] != null ?  Clazz.newArray (-1, [io[7], io[8]]) : io[14] != null ? io[14] : "identity");
}
}, "~A,~N");
c$.getInfoBS = Clazz.defineMethod (c$, "getInfoBS", 
 function (type) {
var bsInfo =  new JU.BS ();
if (type < 0 && -type <= JS.SymmetryDesc.keys.length) {
bsInfo.set (-1 - type);
return bsInfo;
}switch (type) {
case 0:
case 1140850689:
case 1073742001:
case 1073742327:
case 1073741982:
case 1275068418:
bsInfo.setBits (0, JS.SymmetryDesc.keys.length);
break;
case 1073741961:
bsInfo.set (0);
bsInfo.set (2);
break;
case 1145047050:
bsInfo.set (0);
break;
case 1073742078:
bsInfo.set (1);
break;
default:
case 1825200146:
bsInfo.set (2);
break;
case 135176:
bsInfo.set (0);
bsInfo.set (2);
bsInfo.set (3);
break;
case 1145047051:
bsInfo.set (4);
break;
case 1073742178:
bsInfo.set (5);
break;
case 12289:
bsInfo.set (6);
break;
case 134217751:
bsInfo.set (7);
break;
case 1073741854:
bsInfo.set (8);
break;
case 134217729:
bsInfo.set (9);
break;
case 12:
bsInfo.set (10);
break;
case 1814695966:
bsInfo.set (11);
break;
case 4160:
bsInfo.set (12);
break;
case 268435633:
bsInfo.set (13);
break;
case 134217750:
bsInfo.set (14);
break;
case 1140850696:
bsInfo.set (15);
break;
case 1073741974:
bsInfo.set (16);
break;
case 1086326789:
case 36868:
bsInfo.set (5);
bsInfo.set (6);
bsInfo.set (7);
bsInfo.set (8);
bsInfo.set (14);
break;
}
return bsInfo;
}, "~N");
Clazz.defineMethod (c$, "createInfoArray", 
 function (op, uc, pta00, ptTarget, id, scaleFactor, options, haveTranslation, bsInfo) {
if (!op.isFinalized) op.doFinalize ();
var isFractional = bsInfo.get (22);
var matrixOnly = (bsInfo.cardinality () == 1 && bsInfo.get (10) || isFractional);
var isTimeReversed = (op.timeReversal == -1);
if (scaleFactor == 0) scaleFactor = 1;
this.ptemp.set (0, 0, 0);
this.vtrans.set (0, 0, 0);
var plane = null;
if (pta00 == null || Float.isNaN (pta00.x)) pta00 =  new JU.P3 ();
if (ptTarget != null) {
this.pta01.setT (pta00);
this.pta02.setT (ptTarget);
if (!isFractional) {
uc.toFractional (this.pta01, true);
uc.toFractional (this.pta02, true);
}op.rotTrans (this.pta01);
this.ptemp.setT (this.pta01);
uc.unitize (this.pta01);
this.vtrans.setT (this.pta02);
uc.unitize (this.pta02);
if (this.pta01.distanceSquared (this.pta02) > 1.96E-6) return null;
this.vtrans.sub (this.ptemp);
}var m2 = JU.M4.newM4 (op);
m2.add (this.vtrans);
var isMagnetic = (op.timeReversal != 0);
if (matrixOnly && !isMagnetic) {
var im = JS.SymmetryDesc.getKeyType ("matrix");
var o =  new Array (-im);
o[-1 - im] = m2;
return o;
}var ftrans =  new JU.V3 ();
this.pta01.set (1, 0, 0);
this.pta02.set (0, 1, 0);
var pta03 = JU.P3.new3 (0, 0, 1);
this.pta01.add (pta00);
this.pta02.add (pta00);
pta03.add (pta00);
var pt0 = JS.SymmetryDesc.rotTransCart (op, uc, pta00, this.vtrans);
var pt1 = JS.SymmetryDesc.rotTransCart (op, uc, this.pta01, this.vtrans);
var pt2 = JS.SymmetryDesc.rotTransCart (op, uc, this.pta02, this.vtrans);
var pt3 = JS.SymmetryDesc.rotTransCart (op, uc, pta03, this.vtrans);
var vt1 = JU.V3.newVsub (pt1, pt0);
var vt2 = JU.V3.newVsub (pt2, pt0);
var vt3 = JU.V3.newVsub (pt3, pt0);
JS.SymmetryDesc.approx (this.vtrans);
this.vtemp.cross (vt1, vt2);
var haveInversion = (this.vtemp.dot (vt3) < 0);
if (haveInversion) {
pt1.sub2 (pt0, vt1);
pt2.sub2 (pt0, vt2);
pt3.sub2 (pt0, vt3);
}var info = JU.Measure.computeHelicalAxis (pta00, pt0, JU.Quat.getQuaternionFrame (pt0, pt1, pt2).div (JU.Quat.getQuaternionFrame (pta00, this.pta01, this.pta02)));
var pa1 = info[0];
var ax1 = info[1];
var ang1 = Clazz.floatToInt (Math.abs (JU.PT.approx ((info[3]).x, 1)));
var pitch1 = JS.SymmetryOperation.approxF ((info[3]).y);
if (haveInversion) {
pt1.add2 (pt0, vt1);
pt2.add2 (pt0, vt2);
pt3.add2 (pt0, vt3);
}var trans = JU.V3.newVsub (pt0, pta00);
if (trans.length () < 0.1) trans = null;
var ptinv = null;
var ipt = null;
var ptref = null;
var isTranslation = (ang1 == 0);
var isRotation = !isTranslation;
var isInversionOnly = false;
var isMirrorPlane = false;
if (isRotation || haveInversion) {
trans = null;
}if (haveInversion && isTranslation) {
ipt = JU.P3.newP (pta00);
ipt.add (pt0);
ipt.scale (0.5);
ptinv = pt0;
isInversionOnly = true;
} else if (haveInversion) {
var d = (pitch1 == 0 ?  new JU.V3 () : ax1);
var f = 0;
switch (ang1) {
case 60:
f = 0.6666667;
break;
case 120:
f = 2;
break;
case 90:
f = 1;
break;
case 180:
ptref = JU.P3.newP (pta00);
ptref.add (d);
pa1.scaleAdd2 (0.5, d, pta00);
if (ptref.distance (pt0) > 0.1) {
trans = JU.V3.newVsub (pt0, ptref);
JS.SymmetryDesc.setFractional (uc, trans, this.ptemp, null);
ftrans.setT (this.ptemp);
} else {
trans = null;
}isRotation = false;
haveInversion = false;
isMirrorPlane = true;
break;
default:
haveInversion = false;
break;
}
if (f != 0) {
this.vtemp.sub2 (pta00, pa1);
this.vtemp.add (pt0);
this.vtemp.sub (pa1);
this.vtemp.sub (d);
this.vtemp.scale (f);
pa1.add (this.vtemp);
ipt =  new JU.P3 ();
ipt.scaleAdd2 (0.5, d, pa1);
ptinv =  new JU.P3 ();
ptinv.scaleAdd2 (-2, ipt, pta00);
ptinv.scale (-1);
}} else if (trans != null) {
this.ptemp.setT (trans);
uc.toFractional (this.ptemp, false);
ftrans.setT (this.ptemp);
uc.toCartesian (this.ptemp, false);
trans.setT (this.ptemp);
}var ang = ang1;
JS.SymmetryDesc.approx0 (ax1);
if (isRotation) {
var ptr =  new JU.P3 ();
this.vtemp.setT (ax1);
var ang2 = ang1;
if (haveInversion) {
ptr.add2 (pa1, this.vtemp);
ang2 = Math.round (JU.Measure.computeTorsion (ptinv, pa1, ptr, pt0, true));
} else if (pitch1 == 0) {
ptr.setT (pa1);
this.ptemp.scaleAdd2 (1, ptr, this.vtemp);
ang2 = Math.round (JU.Measure.computeTorsion (pta00, pa1, this.ptemp, pt0, true));
} else {
this.ptemp.add2 (pa1, this.vtemp);
ptr.scaleAdd2 (0.5, this.vtemp, pa1);
ang2 = Math.round (JU.Measure.computeTorsion (pta00, pa1, this.ptemp, pt0, true));
}if (ang2 != 0) ang1 = ang2;
if (!haveInversion && pitch1 == 0 && (ax1.z < 0 || ax1.z == 0 && (ax1.y < 0 || ax1.y == 0 && ax1.x < 0))) {
ax1.scale (-1);
ang1 = -ang1;
}}var info1 = null;
var type = null;
if (bsInfo.get (2) || bsInfo.get (15)) {
info1 = type = "identity";
if (isInversionOnly) {
this.ptemp.setT (ipt);
uc.toFractional (this.ptemp, false);
info1 = "Ci: " + JS.SymmetryDesc.strCoord (op, this.ptemp, op.isBio);
type = "inversion center";
} else if (isRotation) {
if (haveInversion) {
type = info1 = (Clazz.doubleToInt (360 / ang)) + "-bar axis";
} else if (pitch1 != 0) {
type = info1 = (Clazz.doubleToInt (360 / ang)) + "-fold screw axis";
this.ptemp.setT (ax1);
uc.toFractional (this.ptemp, false);
info1 += "|translation: " + JS.SymmetryDesc.strCoord (op, this.ptemp, op.isBio);
} else {
type = info1 = "C" + (Clazz.doubleToInt (360 / ang)) + " axis";
}} else if (trans != null) {
var s = " " + JS.SymmetryDesc.strCoord (op, ftrans, op.isBio);
if (isTranslation) {
type = info1 = "translation";
info1 += ":" + s;
} else if (isMirrorPlane) {
var fx = Math.abs (JS.SymmetryOperation.approxF (ftrans.x));
var fy = Math.abs (JS.SymmetryOperation.approxF (ftrans.y));
var fz = Math.abs (JS.SymmetryOperation.approxF (ftrans.z));
s = " " + JS.SymmetryDesc.strCoord (op, ftrans, op.isBio);
if (fx != 0 && fy != 0 && fz != 0) {
if (fx == 0.25 && fy == 0.25 && fz == 0.25) {
info1 = "d-";
} else if (fx == 0.5 && fy == 0.5 && fz == 0.5) {
info1 = "n-";
} else {
info1 = "g-";
}} else if (fx != 0 && fy != 0 || fy != 0 && fz != 0 || fz != 0 && fx != 0) {
if (fx == 0.25 && fy == 0.25 || fx == 0.25 && fz == 0.25 || fy == 0.25 && fz == 0.25) {
info1 = "d-";
} else if (fx == 0.5 && fy == 0.5 || fx == 0.5 && fz == 0.5 || fy == 0.5 && fz == 0.5) {
if (fx == 0 && ax1.x == 0 || fy == 0 && ax1.y == 0 || fz == 0 && ax1.z == 0) {
info1 = "g-";
} else {
info1 = "n-";
}} else {
info1 = "g-";
}} else if (fx != 0) info1 = "a-";
 else if (fy != 0) info1 = "b-";
 else info1 = "c-";
type = info1 = info1 + "glide plane";
info1 += "|translation:" + s;
}} else if (isMirrorPlane) {
type = info1 = "mirror plane";
}if (haveInversion && !isInversionOnly) {
this.ptemp.setT (ipt);
uc.toFractional (this.ptemp, false);
info1 += "|inversion center at " + JS.SymmetryDesc.strCoord (op, this.ptemp, op.isBio);
}if (isTimeReversed) {
info1 += "|time-reversed";
type += " (time-reversed)";
}}var cmds = null;
if (id != null && bsInfo.get (3)) {
var opType = null;
var drawid = "\ndraw ID " + id + "_";
var draw1 =  new JU.SB ();
draw1.append (drawid).append ("* delete");
JS.SymmetryDesc.drawLine (draw1, drawid + "frame1X", 0.15, pta00, this.pta01, "red");
JS.SymmetryDesc.drawLine (draw1, drawid + "frame1Y", 0.15, pta00, this.pta02, "green");
JS.SymmetryDesc.drawLine (draw1, drawid + "frame1Z", 0.15, pta00, pta03, "blue");
var color;
if (isRotation) {
var ptr =  new JU.P3 ();
color = "red";
ang = ang1;
var scale = 1;
this.vtemp.setT (ax1);
if (haveInversion) {
opType = drawid + "rotinv";
ptr.add2 (pa1, this.vtemp);
if (pitch1 == 0) {
ptr.setT (ipt);
this.vtemp.scale (3 * scaleFactor);
this.ptemp.scaleAdd2 (-1, this.vtemp, pa1);
JS.SymmetryDesc.drawVector (draw1, drawid, "rotVector2", "", pa1, this.ptemp, "red");
}scale = pt0.distance (ptr);
draw1.append (drawid).append ("rotLine1 ").append (JU.Escape.eP (ptr)).append (JU.Escape.eP (ptinv)).append (" color red");
draw1.append (drawid).append ("rotLine2 ").append (JU.Escape.eP (ptr)).append (JU.Escape.eP (pt0)).append (" color red");
} else if (pitch1 == 0) {
opType = drawid + "rot";
var isSpecial = (pta00.distance (pt0) < 0.2);
if (!isSpecial) {
draw1.append (drawid).append ("rotLine1 ").append (JU.Escape.eP (pta00)).append (JU.Escape.eP (pa1)).append (" color red");
draw1.append (drawid).append ("rotLine2 ").append (JU.Escape.eP (pt0)).append (JU.Escape.eP (pa1)).append (" color red");
}this.vtemp.scale (3 * scaleFactor);
this.ptemp.scaleAdd2 (-1, this.vtemp, pa1);
JS.SymmetryDesc.drawVector (draw1, drawid, "rotVector2", "", pa1, this.ptemp, isTimeReversed ? "gray" : "red");
ptr.setT (pa1);
if (pitch1 == 0 && pta00.distance (pt0) < 0.2) ptr.scaleAdd2 (0.5, this.vtemp, ptr);
} else {
opType = drawid + "screw";
color = "orange";
draw1.append (drawid).append ("rotLine1 ").append (JU.Escape.eP (pta00)).append (JU.Escape.eP (pa1)).append (" color red");
this.ptemp.add2 (pa1, this.vtemp);
draw1.append (drawid).append ("rotLine2 ").append (JU.Escape.eP (pt0)).append (JU.Escape.eP (this.ptemp)).append (" color red");
ptr.scaleAdd2 (0.5, this.vtemp, pa1);
}this.ptemp.add2 (ptr, this.vtemp);
if (haveInversion && pitch1 != 0) {
draw1.append (drawid).append ("rotRotLine1").append (JU.Escape.eP (ptr)).append (JU.Escape.eP (ptinv)).append (" color red");
draw1.append (drawid).append ("rotRotLine2").append (JU.Escape.eP (ptr)).append (JU.Escape.eP (pt0)).append (" color red");
}draw1.append (drawid).append ("rotRotArrow arrow width 0.1 scale " + JU.PT.escF (scale) + " arc ").append (JU.Escape.eP (ptr)).append (JU.Escape.eP (this.ptemp));
this.ptemp.setT (haveInversion ? ptinv : pta00);
if (this.ptemp.distance (pt0) < 0.1) this.ptemp.set (Math.random (), Math.random (), Math.random ());
draw1.append (JU.Escape.eP (this.ptemp));
this.ptemp.set (0, ang, 0);
draw1.append (JU.Escape.eP (this.ptemp)).append (" color red");
JS.SymmetryDesc.drawVector (draw1, drawid, "rotVector1", "vector", pa1, this.vtemp, isTimeReversed ? "Gray" : color);
} else if (isMirrorPlane) {
this.ptemp.sub2 (ptref, pta00);
if (pta00.distance (ptref) > 0.2) JS.SymmetryDesc.drawVector (draw1, drawid, "planeVector", "vector", pta00, this.ptemp, isTimeReversed ? "Gray" : "cyan");
opType = drawid + "plane";
if (trans != null) {
opType = drawid + "glide";
JS.SymmetryDesc.drawFrameLine ("X", ptref, vt1, 0.15, this.ptemp, draw1, opType, "red");
JS.SymmetryDesc.drawFrameLine ("Y", ptref, vt2, 0.15, this.ptemp, draw1, opType, "green");
JS.SymmetryDesc.drawFrameLine ("Z", ptref, vt3, 0.15, this.ptemp, draw1, opType, "blue");
}color = (trans == null ? "green" : "blue");
this.vtemp.setT (ax1);
this.vtemp.normalize ();
var w = -this.vtemp.x * pa1.x - this.vtemp.y * pa1.y - this.vtemp.z * pa1.z;
plane = JU.P4.new4 (this.vtemp.x, this.vtemp.y, this.vtemp.z, w);
var margin = (Math.abs (w) < 0.01 && this.vtemp.x * this.vtemp.y > 0.4 ? 1.30 : 1.05);
var v = this.modelSet.vwr.getTriangulator ().intersectPlane (plane, uc.getCanonicalCopy (margin, true), 3);
if (v != null) for (var i = v.size (); --i >= 0; ) {
var pts = v.get (i);
draw1.append (drawid).append ("planep").appendI (i).append (" ").append (JU.Escape.eP (pts[0])).append (JU.Escape.eP (pts[1]));
if (pts.length == 3) draw1.append (JU.Escape.eP (pts[2]));
draw1.append (" color translucent ").append (color);
}
if (v == null || v.size () == 0) {
this.ptemp.add2 (pa1, ax1);
draw1.append (drawid).append ("planeCircle scale 2.0 circle ").append (JU.Escape.eP (pa1)).append (JU.Escape.eP (this.ptemp)).append (" color translucent ").append (color).append (" mesh fill");
}}if (haveInversion) {
opType = drawid + "inv";
draw1.append (drawid).append ("invPoint diameter 0.4 ").append (JU.Escape.eP (ipt));
this.ptemp.sub2 (ptinv, pta00);
JS.SymmetryDesc.drawVector (draw1, drawid, "invArrow", "vector", pta00, this.ptemp, isTimeReversed ? "gray" : "cyan");
if (!isInversionOnly && options != 1073742066) {
JS.SymmetryDesc.drawFrameLine ("X", ptinv, vt1, 0.15, this.ptemp, draw1, opType, "red");
JS.SymmetryDesc.drawFrameLine ("Y", ptinv, vt2, 0.15, this.ptemp, draw1, opType, "green");
JS.SymmetryDesc.drawFrameLine ("Z", ptinv, vt3, 0.15, this.ptemp, draw1, opType, "blue");
}}if (trans != null) {
if (ptref == null) ptref = JU.P3.newP (pta00);
JS.SymmetryDesc.drawVector (draw1, drawid, "transVector", "vector", ptref, trans, isTimeReversed && !haveInversion && !isMirrorPlane && !isRotation ? "darkGray" : "gold");
}this.ptemp2.setT (pt0);
this.ptemp.sub2 (pt1, pt0);
this.ptemp.scaleAdd2 (0.9, this.ptemp, this.ptemp2);
JS.SymmetryDesc.drawLine (draw1, drawid + "frame2X", 0.2, this.ptemp2, this.ptemp, "red");
this.ptemp.sub2 (pt2, pt0);
this.ptemp.scaleAdd2 (0.9, this.ptemp, this.ptemp2);
JS.SymmetryDesc.drawLine (draw1, drawid + "frame2Y", 0.2, this.ptemp2, this.ptemp, "green");
this.ptemp.sub2 (pt3, pt0);
this.ptemp.scaleAdd2 (0.9, this.ptemp, this.ptemp2);
JS.SymmetryDesc.drawLine (draw1, drawid + "frame2Z", 0.2, this.ptemp2, this.ptemp, "purple");
draw1.append ("\nsym_point = " + JU.Escape.eP (pta00));
draw1.append ("\nvar p0 = " + JU.Escape.eP (this.ptemp2));
draw1.append ("\nvar set2 = within(0.2,p0);if(!set2){set2 = within(0.2,p0.uxyz.xyz)}");
if (Clazz.instanceOf (pta00, JM.Atom)) draw1.append ("\n set2 &= {_" + (pta00).getElementSymbol () + "}");
draw1.append ("\nsym_target = set2;if (set2) {");
if (options != 1073742066 && ptTarget == null && !haveTranslation) {
draw1.append (drawid).append ("offsetFrameX diameter 0.20 @{set2.xyz} @{set2.xyz + ").append (JU.Escape.eP (vt1)).append ("*0.9} color red");
draw1.append (drawid).append ("offsetFrameY diameter 0.20 @{set2.xyz} @{set2.xyz + ").append (JU.Escape.eP (vt2)).append ("*0.9} color green");
draw1.append (drawid).append ("offsetFrameZ diameter 0.20 @{set2.xyz} @{set2.xyz + ").append (JU.Escape.eP (vt3)).append ("*0.9} color purple");
}draw1.append ("\n}\n");
cmds = draw1.toString ();
if (JU.Logger.debugging) JU.Logger.info (cmds);
draw1 = null;
drawid = null;
}if (trans == null) ftrans = null;
if (isRotation) {
if (haveInversion) {
} else if (pitch1 == 0) {
} else {
trans = JU.V3.newV (ax1);
this.ptemp.setT (trans);
uc.toFractional (this.ptemp, false);
ftrans = JU.V3.newV (this.ptemp);
}}if (isMirrorPlane) {
ang1 = 0;
}if (haveInversion) {
if (isInversionOnly) {
pa1 = null;
ax1 = null;
trans = null;
ftrans = null;
}} else if (isTranslation) {
pa1 = null;
ax1 = null;
}if (ax1 != null) ax1.normalize ();
var xyzNew = null;
if (bsInfo.get (0) || bsInfo.get (17)) {
xyzNew = (op.isBio ? m2.toString () : op.modDim > 0 ? op.xyzOriginal : JS.SymmetryOperation.getXYZFromMatrix (m2, false, false, false));
if (isMagnetic) xyzNew = op.fixMagneticXYZ (m2, xyzNew, true);
}var ret =  new Array (19);
for (var i = bsInfo.nextSetBit (0); i >= 0; i = bsInfo.nextSetBit (i + 1)) {
switch (i) {
case 0:
ret[i] = xyzNew;
break;
case 1:
ret[i] = op.xyzOriginal;
break;
case 2:
ret[i] = info1;
break;
case 3:
ret[i] = cmds;
break;
case 4:
ret[i] = JS.SymmetryDesc.approx0 (ftrans);
break;
case 5:
ret[i] = JS.SymmetryDesc.approx0 (trans);
break;
case 6:
ret[i] = JS.SymmetryDesc.approx0 (ipt);
break;
case 7:
ret[i] = JS.SymmetryDesc.approx0 (pa1);
break;
case 8:
ret[i] = (plane == null ? JS.SymmetryDesc.approx0 (ax1) : null);
break;
case 9:
ret[i] = (ang1 != 0 ? Integer.$valueOf (ang1) : null);
break;
case 10:
ret[i] = m2;
break;
case 11:
ret[i] = (this.vtrans.lengthSquared () > 0 ? this.vtrans : null);
break;
case 12:
ret[i] = op.getCentering ();
break;
case 13:
ret[i] = Integer.$valueOf (op.timeReversal);
break;
case 14:
ret[i] = plane;
break;
case 15:
ret[i] = type;
break;
case 16:
ret[i] = Integer.$valueOf (op.number);
break;
case 17:
var cift = null;
if (!xyzNew.equals (op.xyzOriginal)) {
if (op.number > 0) {
var orig = JS.SymmetryOperation.getMatrixFromXYZ (op.xyzOriginal);
orig.sub (m2);
cift =  new JU.P3 ();
orig.getTranslation (cift);
}}var cifi = (op.number < 0 ? 0 : op.number);
ret[i] = cifi + (cift == null ? " [0 0 0]" : " [" + Clazz.floatToInt (-cift.x) + " " + Clazz.floatToInt (-cift.y) + " " + Clazz.floatToInt (-cift.z) + "]");
break;
case 18:
ret[i] = op.xyzCanonical;
break;
}
}
return ret;
}, "JS.SymmetryOperation,J.api.SymmetryInterface,JU.P3,JU.P3,~S,~N,~N,~B,JU.BS");
c$.drawLine = Clazz.defineMethod (c$, "drawLine", 
 function (s, id, diameter, pt0, pt1, color) {
s.append (id).append (" diameter ").appendF (diameter).append (JU.Escape.eP (pt0)).append (JU.Escape.eP (pt1)).append (" color ").append (color);
}, "JU.SB,~S,~N,JU.P3,JU.P3,~S");
c$.drawFrameLine = Clazz.defineMethod (c$, "drawFrameLine", 
 function (xyz, pt, v, width, ptemp, draw1, key, color) {
ptemp.setT (pt);
ptemp.add (v);
JS.SymmetryDesc.drawLine (draw1, key + "Pt" + xyz, width, pt, ptemp, "translucent " + color);
}, "~S,JU.P3,JU.V3,~N,JU.P3,JU.SB,~S,~S");
c$.drawVector = Clazz.defineMethod (c$, "drawVector", 
 function (draw1, drawid, label, type, pt1, v, color) {
draw1.append (drawid).append (label).append (" diameter 0.1 ").append (type).append (JU.Escape.eP (pt1)).append (JU.Escape.eP (v)).append (" color ").append (color);
}, "JU.SB,~S,~S,~S,JU.T3,JU.T3,~S");
c$.setFractional = Clazz.defineMethod (c$, "setFractional", 
 function (uc, pt00, pt01, offset) {
pt01.setT (pt00);
if (offset != null) uc.toUnitCell (pt01, offset);
uc.toFractional (pt01, false);
}, "J.api.SymmetryInterface,JU.T3,JU.P3,JU.P3");
c$.rotTransCart = Clazz.defineMethod (c$, "rotTransCart", 
 function (op, uc, pt00, vtrans) {
var p0 = JU.P3.newP (pt00);
uc.toFractional (p0, false);
op.rotTrans (p0);
p0.add (vtrans);
uc.toCartesian (p0, false);
return p0;
}, "JS.SymmetryOperation,J.api.SymmetryInterface,JU.P3,JU.V3");
c$.strCoord = Clazz.defineMethod (c$, "strCoord", 
 function (op, p, isBio) {
JS.SymmetryDesc.approx0 (p);
return (isBio ? p.x + " " + p.y + " " + p.z : op.fcoord2 (p));
}, "JS.SymmetryOperation,JU.T3,~B");
c$.approx0 = Clazz.defineMethod (c$, "approx0", 
 function (pt) {
if (pt != null) {
if (Math.abs (pt.x) < 0.0001) pt.x = 0;
if (Math.abs (pt.y) < 0.0001) pt.y = 0;
if (Math.abs (pt.z) < 0.0001) pt.z = 0;
}return pt;
}, "JU.T3");
c$.approx = Clazz.defineMethod (c$, "approx", 
 function (pt) {
if (pt != null) {
pt.x = JS.SymmetryOperation.approxF (pt.x);
pt.y = JS.SymmetryOperation.approxF (pt.y);
pt.z = JS.SymmetryOperation.approxF (pt.z);
}return pt;
}, "JU.T3");
Clazz.defineMethod (c$, "getSymmetryInfo", 
 function (sym, iModel, iatom, uc, xyz, op, translation, pt, pt2, id, type, scaleFactor, nth, options) {
var returnType = 0;
var nullRet = JS.SymmetryDesc.nullReturn (type);
switch (type) {
case 1073741994:
return "" + uc.getLatticeType ();
case 1073742001:
returnType = 1825200146;
break;
case 1275068418:
returnType = JS.SymmetryDesc.getType (id);
switch (returnType) {
case 1140850689:
case 135176:
case 1073741961:
case 1073742001:
case 134217751:
case 1086326789:
case 36868:
type = returnType;
break;
default:
returnType = JS.SymmetryDesc.getKeyType (id);
break;
}
break;
}
var bsInfo = JS.SymmetryDesc.getInfoBS (returnType);
var iop = op;
var offset = (options == 1073742066 && (type == 1140850689 || type == 134217751) ? pt2 : null);
if (offset != null) pt2 = null;
var info = null;
var xyzOriginal = null;
if (pt2 == null) {
if (xyz == null) {
var ops = uc.getSymmetryOperations ();
if (ops == null || op == 0 || Math.abs (op) > ops.length) return nullRet;
iop = Math.abs (op) - 1;
xyz = (translation == null ? ops[iop].xyz : ops[iop].getxyzTrans (translation));
xyzOriginal = ops[iop].xyzOriginal;
} else {
iop = op = 0;
}var symTemp =  new JS.Symmetry ();
symTemp.setSpaceGroup (false);
var isBio = uc.isBio ();
var i = (isBio ? symTemp.addBioMoleculeOperation ((uc.getSpaceGroup ()).finalOperations[iop], op < 0) : symTemp.addSpaceGroupOperation ((op < 0 ? "!" : "=") + xyz, Math.abs (op)));
if (i < 0) return nullRet;
var opTemp = symTemp.getSpaceGroupOperation (i);
if (xyzOriginal != null) opTemp.xyzOriginal = xyzOriginal;
opTemp.number = op;
if (!isBio) opTemp.getCentering ();
if (pt == null && iatom >= 0) pt = this.modelSet.at[iatom];
if (type == 134217751 || type == 1140850689) {
if (isBio) return nullRet;
symTemp.setUnitCell (uc);
pt = JU.P3.newP (pt);
uc.toFractional (pt, false);
if (Float.isNaN (pt.x)) return nullRet;
var sympt =  new JU.P3 ();
symTemp.newSpaceGroupPoint (i, pt, sympt, 0, 0, 0, null);
if (options == 1073742066) {
uc.unitize (sympt);
if (options == 1073742066) sympt.add (offset);
}symTemp.toCartesian (sympt, false);
return (type == 1140850689 ? this.getAtom (uc, iModel, iatom, sympt) : sympt);
}info = this.createInfoArray (opTemp, uc, pt, null, (id == null ? "sym" : id), scaleFactor, options, (translation != null), bsInfo);
if (type == 1275068418 && id != null) {
returnType = JS.SymmetryDesc.getKeyType (id);
}} else {
var stype = "info";
var asString = false;
switch (type) {
case 1275068418:
returnType = JS.SymmetryDesc.getKeyType (id);
id = stype = null;
if (nth == 0) nth = -1;
break;
case 1073742001:
id = stype = null;
if (nth == 0) nth = -1;
asString = true;
bsInfo.set (21);
break;
case 135176:
if (id == null) id = "sym";
stype = "all";
asString = true;
break;
case 1140850689:
id = stype = null;
default:
if (nth == 0) nth = 1;
}
var ret1 = this.getSymopInfoForPoints (sym, iModel, op, translation, pt, pt2, id, stype, scaleFactor, nth, options, bsInfo);
if (asString) return ret1;
if (Clazz.instanceOf (ret1, String)) return nullRet;
info = ret1;
if (type == 1140850689) {
if (!(Clazz.instanceOf (pt, JM.Atom)) && !(Clazz.instanceOf (pt2, JM.Atom))) iatom = -1;
return (info == null ? nullRet : this.getAtom (uc, iModel, iatom, info[7]));
}}if (info == null) return nullRet;
var isList = (info.length > 0 && Clazz.instanceOf (info[0], Array));
if (nth < 0 && op <= 0 && (type == 1275068418 || isList)) {
if (type == 1275068418 && info.length > 0 && !(Clazz.instanceOf (info[0], Array))) info =  Clazz.newArray (-1, [info]);
var lst =  new JU.Lst ();
for (var i = 0; i < info.length; i++) lst.addLast (JS.SymmetryDesc.getInfo (info[i], returnType < 0 ? returnType : type));

return lst;
} else if (returnType < 0 && (nth >= 0 || op > 0)) {
type = returnType;
}if (nth > 0 && isList) info = info[0];
return JS.SymmetryDesc.getInfo (info, type);
}, "J.api.SymmetryInterface,~N,~N,J.api.SymmetryInterface,~S,~N,JU.P3,JU.P3,JU.P3,~S,~N,~N,~N,~N");
Clazz.defineMethod (c$, "getAtom", 
 function (uc, iModel, iAtom, sympt) {
var bsElement = null;
if (iAtom >= 0) this.modelSet.getAtomBitsMDa (1094715402, Integer.$valueOf (this.modelSet.at[iAtom].getElementNumber ()), bsElement =  new JU.BS ());
var bsResult =  new JU.BS ();
this.modelSet.getAtomsWithin (0.02, sympt, bsResult, iModel);
if (bsElement != null) bsResult.and (bsElement);
if (bsResult.isEmpty ()) {
sympt = JU.P3.newP (sympt);
uc.toUnitCell (sympt, null);
uc.toCartesian (sympt, false);
this.modelSet.getAtomsWithin (0.02, sympt, bsResult, iModel);
if (bsElement != null) bsResult.and (bsElement);
}return bsResult;
}, "J.api.SymmetryInterface,~N,~N,JU.T3");
Clazz.defineMethod (c$, "getSymopInfoForPoints", 
function (sym, modelIndex, symOp, translation, pt1, pt2, drawID, stype, scaleFactor, nth, options, bsInfo) {
var asString = (bsInfo.get (21) || bsInfo.get (3) && bsInfo.cardinality () == 3);
bsInfo.clear (21);
var ret = (asString ? "" : null);
var sginfo = this.getSpaceGroupInfo (sym, modelIndex, null, symOp, pt1, pt2, drawID, scaleFactor, nth, false, true, options, null, bsInfo);
if (sginfo == null) return ret;
var infolist = sginfo.get ("operations");
if (infolist == null) return ret;
var sb = (asString ?  new JU.SB () : null);
symOp--;
var isAll = (!asString && symOp < 0);
var strOperations = sginfo.get ("symmetryInfo");
var labelOnly = "label".equals (stype);
var n = 0;
for (var i = 0; i < infolist.length; i++) {
if (infolist[i] == null || symOp >= 0 && symOp != i) continue;
if (!asString) {
if (!isAll) return infolist[i];
infolist[n++] = infolist[i];
continue;
}if (drawID != null) return (infolist[i][3]) + "\nprint " + JU.PT.esc (strOperations);
if (sb.length () > 0) sb.appendC ('\n');
if (!labelOnly) {
if (symOp < 0) sb.appendI (i + 1).appendC ('\t');
sb.append (infolist[i][0]).appendC ('\t');
}sb.append (infolist[i][2]);
}
if (!asString) {
var a =  new Array (n);
for (var i = 0; i < n; i++) a[i] = infolist[i];

return a;
}if (sb.length () == 0) return (drawID != null ? "draw " + drawID + "* delete" : ret);
return sb.toString ();
}, "J.api.SymmetryInterface,~N,~N,JU.P3,JU.P3,JU.P3,~S,~S,~N,~N,~N,JU.BS");
Clazz.defineMethod (c$, "getSymopInfo", 
function (iAtom, xyz, op, translation, pt, pt2, id, type, scaleFactor, nth, options) {
if (type == 0) type = JS.SymmetryDesc.getType (id);
var ret = (type == 1140850689 ?  new JU.BS () : "");
if (iAtom < 0) return ret;
var iModel = this.modelSet.at[iAtom].mi;
var uc = this.modelSet.am[iModel].biosymmetry;
if (uc == null && (uc = this.modelSet.getUnitCell (iModel)) == null) return ret;
if (type != 135176 || op != 2147483647) {
return this.getSymmetryInfo (uc, iModel, iAtom, uc, xyz, op, translation, pt, pt2, id, type, scaleFactor, nth, options);
}var s = "";
var ops = uc.getSymmetryOperations ();
if (ops != null) {
if (id == null) id = "sg";
var n = ops.length;
for (op = 0; op < n; op++) s += this.getSymmetryInfo (uc, iModel, iAtom, uc, xyz, op, translation, pt, pt2, id + op, 135176, scaleFactor, nth, options);

}return s;
}, "~N,~S,~N,JU.P3,JU.P3,JU.P3,~S,~N,~N,~N,~N");
Clazz.defineMethod (c$, "getSpaceGroupInfo", 
function (sym, modelIndex, sgName, symOp, pt1, pt2, drawID, scaleFactor, nth, isFull, isForModel, options, cellInfo, bsInfo) {
if (bsInfo == null) {
bsInfo =  new JU.BS ();
bsInfo.set (0);
bsInfo.set (2);
}var matrixOnly = (bsInfo.cardinality () == 1 && bsInfo.get (10) || bsInfo.get (22));
var info = null;
var isStandard = (pt1 == null && drawID == null && nth <= 0);
var isBio = false;
var sgNote = null;
var haveName = (sgName != null && sgName.length > 0);
var haveRawName = (haveName && sgName.indexOf ("[--]") >= 0);
if (isForModel || !haveName) {
var saveModelInfo = (isStandard && symOp == 0);
if (modelIndex < 0) modelIndex = (Clazz.instanceOf (pt1, JM.Atom) ? (pt1).mi : this.modelSet.vwr.am.cmi);
if (modelIndex < 0) sgNote = "no single current model";
 else if (cellInfo == null && !(isBio = (cellInfo = this.modelSet.am[modelIndex].biosymmetry) != null) && (cellInfo = this.modelSet.getUnitCell (modelIndex)) == null) sgNote = "not applicable";
if (sgNote != null) {
info =  new java.util.Hashtable ();
info.put ("spaceGroupInfo", "");
info.put ("spaceGroupNote", sgNote);
info.put ("symmetryInfo", "");
} else if (isStandard) {
info = this.modelSet.getInfo (modelIndex, "spaceGroupInfo");
}if (info != null) return info;
info =  new java.util.Hashtable ();
sgName = cellInfo.getSpaceGroupName ();
var ops = cellInfo.getSymmetryOperations ();
var sg = (isBio ? (cellInfo).spaceGroup : null);
var slist = (haveRawName ? "" : null);
var opCount = 0;
if (ops != null) {
if (!matrixOnly) {
if (isBio) sym.setSpaceGroupTo (JS.SpaceGroup.getNull (false, false, false));
 else sym.setSpaceGroup (false);
}if (ops.length == 0) {
cellInfo.addSpaceGroupOperation ("x,y,z", 1);
cellInfo.setFinalOperations (null, null, 0, 0, false, null);
cellInfo.getSpaceGroupOperation (0);
ops = cellInfo.getSymmetryOperations ();
}if (ops[0].timeReversal != 0) (sym.getSpaceGroupOperation (0)).timeReversal = 1;
var infolist =  new Array (ops.length);
var sops = "";
for (var i = 0, nop = 0; i < ops.length && nop != nth; i++) {
var op = ops[i];
var xyzOriginal = op.xyzOriginal;
var iop;
if (matrixOnly) {
iop = i;
} else {
var isNewIncomm = (i == 0 && op.xyz.indexOf ("x4") >= 0);
iop = (!isNewIncomm && sym.getSpaceGroupOperation (i) != null ? i : isBio ? sym.addBioMoleculeOperation (sg.finalOperations[i], false) : sym.addSpaceGroupOperation ("=" + op.xyz, i + 1));
if (iop < 0) continue;
op = sym.getSpaceGroupOperation (i);
if (op == null) continue;
op.xyzOriginal = xyzOriginal;
}if (op.timeReversal != 0 || op.modDim > 0) isStandard = false;
if (slist != null) slist += ";" + op.xyz;
var ret = (symOp > 0 && symOp - 1 != iop ? null : this.createInfoArray (op, cellInfo, pt1, pt2, drawID, scaleFactor, options, false, bsInfo));
if (ret != null) {
if (nth > 0 && ++nop != nth) continue;
infolist[i] = ret;
if (!matrixOnly) sops += "\n" + (i + 1) + "\t" + ret[0] + "\t" + ret[2];
opCount++;
}}
info.put ("operations", infolist);
if (!matrixOnly) info.put ("symmetryInfo", (sops.length == 0 ? "" : sops.substring (1)));
}if (matrixOnly) {
return info;
}sgNote = (opCount == 0 ? "\n no symmetry operations" : nth <= 0 && symOp <= 0 ? "\n" + opCount + " symmetry operation" + (opCount == 1 ? ":\n" : "s:\n") : "");
if (slist != null) sgName = slist.substring (slist.indexOf (";") + 1);
if (saveModelInfo) this.modelSet.setInfo (modelIndex, "spaceGroupInfo", info);
} else {
info =  new java.util.Hashtable ();
}info.put ("spaceGroupName", sgName);
info.put ("spaceGroupNote", sgNote == null ? "" : sgNote);
var data;
if (isBio) {
data = sgName;
} else {
if (haveName && !haveRawName) sym.setSpaceGroupName (sgName);
data = sym.getSpaceGroupInfoObj (sgName, cellInfo, isFull, !isForModel);
if (data == null || data.equals ("?")) {
data = "?";
info.put ("spaceGroupNote", "could not identify space group from name: " + sgName + "\nformat: show spacegroup \"2\" or \"P 2c\" " + "or \"C m m m\" or \"x, y, z;-x ,-y, -z\"");
}}info.put ("spaceGroupInfo", data);
return info;
}, "J.api.SymmetryInterface,~N,~S,~N,JU.P3,JU.P3,~S,~N,~N,~B,~B,~N,J.api.SymmetryInterface,JU.BS");
Clazz.defineStatics (c$,
"RET_XYZ", 0,
"RET_XYZORIGINAL", 1,
"RET_LABEL", 2,
"RET_DRAW", 3,
"RET_FTRANS", 4,
"RET_CTRANS", 5,
"RET_INVCTR", 6,
"RET_POINT", 7,
"RET_AXISVECTOR", 8,
"RET_ROTANGLE", 9,
"RET_MATRIX", 10,
"RET_UNITTRANS", 11,
"RET_CTRVECTOR", 12,
"RET_TIMEREV", 13,
"RET_PLANE", 14,
"RET_TYPE", 15,
"RET_ID", 16,
"RET_CIF2", 17,
"RET_XYZCANON", 18,
"RET_COUNT", 19,
"RET_LIST", 21,
"RET_TRANSFORMONLY", 22,
"keys",  Clazz.newArray (-1, ["xyz", "xyzOriginal", "label", null, "fractionalTranslation", "cartesianTranslation", "inversionCenter", null, "axisVector", "rotationAngle", "matrix", "unitTranslation", "centeringVector", "timeReversal", "plane", "_type", "id", "cif2", "xyzCanonical"]));
});
