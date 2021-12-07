import {NodeFemale} from './nodesfront.js'

export let pageText;

async function loadRoot() {
  const pageelmtsmum=await (new NodeFemale("TABLE_PAGEELEMENTS", "TABLE_PAGEELEMENTS")).loadRequest("get my root");
  //if no root means that table domelements doesn't exist or has no elements
  if (pageelmtsmum.children.length==0) {
    throw new Error('Database Content Error');
  }
  return pageText=pageelmtsmum.getChild();
}

async function loadText() {
  if (!pageText) await loadRoot();
  const {currentLanguage} = await import("./languages.js");
  //We load menus and its relationships. We want to load menus elementsdata children but not elements children
  return await pageText.loadRequest("get my tree", {extraParents: currentLanguage.getRelationship("pageelementsdata"), deepLevel: 4});
}

export {loadText};