import {startThemes} from './themesback.js';
import {Node, NodeMale, NodeFemale, dataToNode} from './nodesback.js';
import {dbGetTables, dbInitDb, dbGetDbLink} from './dbgateway.js';
import {User, userLogin} from './user.js';
import {isAllowedToRead, isAllowedToInsert, isAllowedToModify} from './safety.js';
  
const requestsAuth=new Map();

requestsAuth.set('check requirements', ()=>true);

requestsAuth.set('check db link', ()=>{if (dbGetDbLink(true)) return true;});

requestsAuth.set('get themes tree', ()=>startThemes().avoidrecursion());

requestsAuth.set('get tables', dbGetTables);

requestsAuth.set('init database', dbInitDb);

requestsAuth.set('report', ()=>import('./reports.js').then(({makeReport})=>makeReport(parameters.repData)));

//<-- User requestsAuth

requestsAuth.set('logout', User.logout);

requestsAuth.set('login', (parameters)=>
  userLogin(parameters.user_name, parameters.user_password)
  .then(result =>{
    if (result instanceof Error) return result.message;
    return result.avoidrecursion();
  })
);

requestsAuth.set('update user pwd', (parameters)=>User.dbUpdatePwd(parameters.user_name, parameters.user_password));

requestsAuth.set('update my user pwd', (parameters, user)=>user.dbUpdateMyPwd(parameters.user_password));

requestsAuth.set('create user', (parameters)=>
  User.create(parameters.user_name, parameters.user_password, parameters.user_email)
  .then(result=>{
    if (result instanceof Error) return result.message;
    return result.props.id;
  })
);

requestsAuth.set('send mail', (parameters, user)=>user.sendMail(parameters.to, parameters.subject, parameters.message, parameters.from));

//<-- Read requestsAuth

requestsAuth.set('get my childtablekeys', (parameters)=>NodeFemale.dbGetChildTableKeys(parameters.nodeData));

requestsAuth.set('get my root', (parameters, user)=>NodeFemale.dbGetRoot(parameters.nodeData), ()=>isAllowedToRead(user, parameters.nodeData));

requestsAuth.set('get all my children', async (parameters, user)=>{
  if (! await isAllowedToRead(user, parameters.nodeData)) throw new Error("Database safety");
  return NodeFemale.dbGetAllChildren(parameters.nodeData, parameters.filterProps, parameters.limit)
  .then(result=>{
    for (const child of result.data) {
      child.avoidrecursion();
    }
    return result;
  })
});
  
requestsAuth.set('get my children', async (parameters, user)=>{
  if (! await isAllowedToRead(user, parameters.nodeData)) throw new Error("Database safety");
  return NodeFemale.dbGetChildren(parameters.nodeData, parameters.extraParents, parameters.filterProps, parameters.limit)
  .then(result=>{
    for (const child of result.data) {
      child.avoidrecursion();
    }
    return result;
  })
});
  
requestsAuth.set('get my tree', async (parameters, user)=>{
  if (! await isAllowedToRead(user, parameters.nodeData)) throw new Error("Database safety");
  const req = dataToNode(parameters.nodeData);
  return req.dbLoadMyTree(parameters.extraParents, parameters.deepLevel, parameters.filterProps, parameters.limit, parameters.myself)
  .then(result=>{
    const elements=Node.detectGender(parameters.nodeData)=="female" ? result.data : !parameters.myself ? result : [];
    for (const element of elements) {
      element.avoidrecursion();
    }
    if (parameters.myself) result.avoidrecursion();
    return result;
  });
});

requestsAuth.set('get my  tree', async (parameters, user)=>{
  if (! await isAllowedToRead(user, parameters.nodeData)) throw new Error("Database safety");
  const req = Node.detectGender(parameters.nodeData)=="female" ? new NodeFemale() : new NodeMale();
  req.load(parameters.nodeData);
  return req.dbGetMyTreeUp(parameters.deepLevel)
  .then(upElements=>{
    if (Array.isArray(upElements)) {
      for (const upNode of upElements) {
        upNode.avoidrecursion();
      }
    }
    else upElements.avoidrecursion();
    return upElements;
  });
});

requestsAuth.set('get my relationships', (parameters, user)=>
  NodeMale.dbGetRelationships(parameters.nodeData)
  .then(relationships=>{
    for (const rel of relationships) {
      rel.avoidrecursion();
    }
    return relationships;
  })
);
  
//<-- Insert requestsAuth

requestsAuth.set('add myself', async (parameters, user)=>{
  if (! await isAllowedToInsert(user, parameters.nodeData)) throw new Error("Database safety");
  return (new NodeMale()).load(parameters.nodeData).dbInsertMySelf(parameters.extraParents);
});

requestsAuth.set('add my children', async (parameters, user)=>{
  if (! await isAllowedToInsert(user, parameters.nodeData)) throw new Error("Database safety");
  return (new NodeFemale()).load(parameters.nodeData).dbInsertMyChildren(parameters.extraParents);
});
  
requestsAuth.set('add my tree', async (parameters, user)=>{
  if (! await isAllowedToInsert(user, parameters.nodeData)) throw new Error("Database safety");
  const req = Node.detectGender(parameters.nodeData)=="female" ? new NodeFemale() : new NodeMale();
  return req.load(parameters.nodeData).dbInsertMyTree(parameters.deepLevel, parameters.extraParents, parameters.myself)
  .then(result=>result.avoidrecursion());
});

requestsAuth.set('add my tree table content', async (parameters, user)=>{
  if (! await isAllowedToInsert(user, parameters.nodeData)) throw new Error("Database safety");
  const req = Node.detectGender(parameters.nodeData)=="female" ? new NodeFemale() : new NodeMale();
  return req.load(parameters.nodeData).dbInsertMyTreeTableContent(parameteres.tableName, parameteres.deepLevel, parameteres.extraParents)
  .then(elements=>{
    const elementsIds=[];
    for (const elm of elements) {
      if (elm.props.id) elementsIds.push(elm.props.id);
    }
    return elementsIds;
  });
});

requestsAuth.set('add my link', async (parameters, user)=>{
  if (! await isAllowedToInsert(user, parameters.nodeData)) throw new Error("Database safety");
  return (new NodeMale()).load(parameters.nodeData).dbInsertMyLink(parameters.extraParents);
});

//<-- Delete queries

requestsAuth.set('delete myself', async (parameters, user)=>{
  if (! await isAllowedToModify(user, parameters.nodeData)) throw new Error("Database safety");
  return (new NodeMale()).load(parameters.nodeData).dbDeleteMySelf();
});

requestsAuth.set('delete my tree', async (parameters, user)=>{
  if (! await isAllowedToModify(user, parameters.nodeData)) throw new Error("Database safety");
  const req = Node.detectGender(parameters.nodeData)=="female" ? new NodeFemale() : new NodeMale();
  return req.load(parameters.nodeData).dbDeleteMyTree();
});

requestsAuth.set('delete my children', async (parameters, user)=>{
  if (! await isAllowedToModify(user, parameters.nodeData)) throw new Error("Database safety");
  return (new NodeFemale()).load(parameters.nodeData).dbDeleteMyChildren();
});

requestsAuth.set('delete my link', async (parameters, user)=>{
  if (! await isAllowedToModify(user, parameters.nodeData)) throw new Error("Database safety");
  return (new NodeMale()).load(parameters.nodeData).dbDeleteMyLink();
});

//<-- Update queries

requestsAuth.set('edit my props', async (parameters, user)=>{
  if (! await isAllowedToModify(user, parameters.nodeData)) throw new Error("Database safety");
  return (new NodeMale()).load(parameters.nodeData).dbUpdateMyProps(parameters.props);
});
  
requestsAuth.set('edit my sort_order', async (parameters, user)=>{
  if (! await isAllowedToModify(user, parameters.nodeData)) throw new Error("Database safety");
  return (new NodeMale()).load(parameters.nodeData).dbUpdateMySortOrder(parameters.newSortOrder)
  .then(afected=>{
    if (afected==1) return parameters.newSortOrder;
    else return false;
  });
});
  
export default requestsAuth;
