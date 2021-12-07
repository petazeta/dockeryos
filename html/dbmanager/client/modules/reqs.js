import {NodeMale, NodeFemale} from './nodesfront.js';

const reduceExtraParents = (params)=>{
  if (params.extraParents) {
    if (!Array.isArray(params.extraParents)) params.extraParents=[params.extraParents];
    params.extraParents=params.extraParents.map(eParent=>{
      if (eParent.avoidrecursion) {
        eParent=eParent.cloneNode(1, 0, "id", "id");
        eParent.avoidrecursion();
      }
      return eParent;
    });
  }
  return params;
};

const reqReduc = new Map();
const reqLoaders = new Map();

reqReduc.set("get my root", (myNode)=>myNode.cloneNode(0, 0));
reqLoaders.set("get my root", (myNode, result)=>{
  myNode.children=[];
  if (result!==false) myNode.addChild(new NodeMale().load(result));
});
reqReduc.set("get my childtablekeys", reqReduc.get("get my root"));
reqReduc.set("get my relationships", (myNode)=>myNode.cloneNode(1, 0, "id", "id"));
reqLoaders.set("get my relationships", (myNode, result)=>{
  myNode.relationships=[];
  result.forEach(rel=>myNode.addRelationship((new NodeFemale()).load(rel)));
});
reqReduc.set("get my children", [reqReduc.get("get my relationships"), reduceExtraParents]);
reqLoaders.set("get my children", (myNode, result)=>{
  myNode.children=[];
  result.data.forEach(child=>myNode.addChild(new NodeMale().load(child)));
  myNode.props.total=result.total;
});
reqReduc.set("get all my children", reqReduc.get("get my root"));
reqLoaders.set("get all my children", reqLoaders.get("get my children"));
reqReduc.set("get my tree", [reqReduc.get("get my relationships"), reduceExtraParents]);
reqLoaders.set("get my tree", (myNode, result, params)=>{
  if (myNode.detectMyGender()=="female") {
    myNode.children=[];
    for (const element of result.data) {
      myNode.addChild(new NodeMale().load(element));
    }
    myNode.props.total=result.total;
  }
  else {
    if (params && params.myself) {
      myNode.load(result);
      return;
    }
    myNode.relationships=[];
    for (const element of result) {
      myNode.addRelationship((new NodeFemale()).load(element));
    }
  }
});
reqReduc.set("edit my sort_order", (myNode)=>myNode.cloneNode(3, 0, "id", "id")); // we need the parent->partner (and parent->partner->parent for safety check)
reqReduc.set("delete my link", reqReduc.get("edit my sort_order"));
reqReduc.set("add my link", [ (myNode)=>myNode.cloneNode(3, 0, null, "id"), reduceExtraParents]); //we are keeping the props cause we need the sort_order positioning prop
reqReduc.set("add myself", [(myNode)=>myNode.cloneNode(3, 0, null, "id"), reduceExtraParents]);
reqLoaders.set("add myself", (myNode, result)=>{
  myNode.props.id=result;
});
reqReduc.set("add my children", [(myNode)=>myNode.cloneNode(2, 1, "id", "id"), reduceExtraParents]); // we need the partner (and partner->parent for safety check)
reqLoaders.set("add my children", (myNode, result)=>{
  for (const i in result) {
    myNode.children[i].props.id=result[i];
  }
});
reqReduc.set("delete my children", reqReduc.get("add my children"));
reqReduc.set("delete my tree", (myNode)=>myNode.cloneNode(2, 0, null, "id")); // we need the partner for update siblings position
reqReduc.set("delete myself", reqReduc.get("delete my tree"));
reqReduc.set("edit my props", reqReduc.get("delete my tree"));
reqReduc.set("get my parent", (myNode)=>myNode.cloneNode(1, 1, "id"));
reqReduc.set("get my tree up", reqReduc.get("get my parent"));
reqLoaders.set("get my tree up", (myNode, result)=>{
  if (myNode.detectMyGender()=="female") {
    myNode.partnerNode=new NodeMale().load(result);
    myNode.partnerNode.addRelationship(this);
  }
  else {
    if (Array.isArray(result)) {
      myNode.parentNode=[];
      for (const i in result) {
        myNode.parentNode[i]=(new NodeFemale()).load(result[i]);
        myNode.parentNode[i].addChild(this);
      }
    }
    else {
      myNode.parentNode=(new NodeFemale()).load(result);
      myNode.parentNode.addChild(this);
    }
  }
});
reqReduc.set("add my tree", [(myNode)=>myNode.cloneNode(3, null, null, "id"), reduceExtraParents]); // we need the parent->partner (and parent->partner->parent for safety check)
reqLoaders.set("add my tree", (myNode, result)=>{
  if (result.props.id)  myNode.props.id=result.props.id; //female has no id
  myNode.loaddesc(result);
});
reqReduc.set("add my tree table content", [reqReduc.get("add my tree"), reduceExtraParents]);

export {reqReduc, reqLoaders};