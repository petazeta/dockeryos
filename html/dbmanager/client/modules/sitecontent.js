import {NodeFemale} from './nodesfront.js'

export let siteText;

async function loadRoot() {
  const siteelmtsmum=await (new NodeFemale("TABLE_SITEELEMENTS", "TABLE_SITEELEMENTS")).loadRequest("get my root");
  //if no root means that table domelements doesn't exist or has no elements
  if (siteelmtsmum.children.length==0) {
    throw new Error('Database Content Error');
  }
  return siteText=siteelmtsmum.getChild();
}

async function loadText() {
  if (!siteText) await loadRoot();
  const {currentLanguage} = await import("./languages.js");
  return await siteText.loadRequest("get my tree", {extraParents: currentLanguage.getRelationship("siteelementsdata")});
}

export {loadText};