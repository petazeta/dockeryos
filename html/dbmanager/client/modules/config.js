import Config from './default.js'
export default Config;
Config.themeId= "root"; //Select the theme
Config.styleId= "dbadmin"; //Select the style
Config.languagesOn= true; //true or false if deactivated
Config.initUrl= ''; //Value= ?menu=32 or ?category=5&subcategory=5&item=4 (default= it will init at first menu.)
Config.chktuserdata_On=true;
Config.chktshipping_On=true;
Config.chktpayment_On=true;

Config.statsOn=false;
Config.viewsCacheOn=true; //true, sometimes false for development
Config.loadViewsAtOnce=true; //true usually for a better performance
//Config.catalogImagesSmallPath= "https://";
//Config.requestFilePath= "https://";
