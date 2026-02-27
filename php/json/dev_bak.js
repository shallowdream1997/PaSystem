module.exports = function (METADATA) {
    return {
        'ENV': JSON.stringify(METADATA.ENV),
        'HMR': METADATA.HMR,
        'process.env': {
            'ENV': JSON.stringify(METADATA.ENV),
            'NODE_ENV': JSON.stringify(METADATA.ENV),
            'HMR': METADATA.HMR,
            'UX168_IMG_URL': JSON.stringify('https://ali-productimages.ux168.com/photo_new_watermark'),
            'POMS_VPCAPI_URL': JSON.stringify('https://vpcapi.ux168.cn:8243/poms/1.0'),
            'POMS_VPCAPI_AUTH': JSON.stringify('Bearer f90402e9-f893-3948-bf7d-abe4181867e4'),
            //dev
            'UX168_URL': JSON.stringify('https://master-nodejs-168.ux168.cn/api'),
            'UX168_KEY': JSON.stringify(''),
            'POMS_URL': JSON.stringify('https://master-nodejs-poms.ux168.cn/api'),
            'POMS_KEY': JSON.stringify(''),
            'QMS_URL': JSON.stringify('https://master-nodejs-poms-qms.ux168.cn/api'),
            'QMS_KEY': JSON.stringify(''),
            'SM_URL': JSON.stringify('https://master-nodejs-stockmanage.ux168.cn/api'),
            'SM_KEY': JSON.stringify(''),
            "SM_TOTAL_URL": JSON.stringify("https://master-nodejs-stockmanagetotal.ux168.cn/api"),
            "SM_TOTAL_KEY": JSON.stringify(""),
            'SF_URL': JSON.stringify('https://master-nodejs-salesforecast.ux168.cn/api'),
            'SF_KEY': JSON.stringify(''),
            "CETS_URL": JSON.stringify("https://master-nodejs-cets.ux168.cn/api"),
            "CETS_KEY": JSON.stringify(""),
            'POMS_PHPREST_URL': JSON.stringify('https://alivpc-slim-poms.ux168.cn/api'),
            'POMS_HKVPC_PHPREST_URL': JSON.stringify('https://hk-alivpc-slim-poms.ux168.cn/api'),
            'POMS_PHPREST_KEY': JSON.stringify(''),
            'POMS_PRODUCT_URL': JSON.stringify('https://master-angular-nodejs-poms-list-manage.ux168.cn/api'),
            'POMS_PRODUCT_KEY': JSON.stringify(''),
            'POMS_ERROR_RECORD': JSON.stringify('https://master-nodejs-poms-list-manage.ux168.cn/api'),
            'SCRIPT_POMS_PRODUCT_URL': JSON.stringify('https://master-script-nodejs-poms-list-manage.ux168.cn/api'),
            'SCRIPT_POMS_PRODUCT_KEY': JSON.stringify(''),
            "POMS_CHANNEL_URL": JSON.stringify("https://master-nodejs-poms-channel.ux168.cn/api"),
            'POMS_SOLD_URL': JSON.stringify('https://master-nodejs-poms-sold.ux168.cn/api'),
            'POMS_SOLD_KEY': JSON.stringify(''),
            'INVENTORY_URL': JSON.stringify('https://master-nodejs-inventory.ux168.cn/api'),
            'INVENTORY_KEY': JSON.stringify(''),
            //当香港访问alivpc网络故障时，跳过api gateway使用下面的设置
//          'POMS_URL' : JSON.stringify('http://192.168.169.153:60009/api'),
//          'POMS_KEY' : JSON.stringify(''),
            "OPERATION_CENTER_APP_URL_REST_MASTER": JSON.stringify("https://master-nodejs-opc.ux168.cn/api"),
            "OPERATION_CENTER_APP_URL_REST_QUERY": JSON.stringify("https://master-nodejs-opc.ux168.cn/api"),
            "OPERATION_CENTER_APP_URL_REST_KEY": JSON.stringify(''),
            'USER_INFORMATION_URL': JSON.stringify('http://master.api.gateway.ux168.cn/ux168_composite/ucenters'),
            'QMS_IMAGE_URL': JSON.stringify('https://ali-productimages.ux168.com/qms/'),
            'CETS_RETURN_PRODUCT_IMAGE_URL': JSON.stringify('https://ali-productimages.ux168.com/return_product/'),
            'CETS_TORT_PRODUCT_IMAGE_URL': JSON.stringify('https://ali-productimages.ux168.com/tort_product/'),
            'USER_CENTER_CLIENT_URL': JSON.stringify("http://172.16.29.23:90/getUserSandbox49.php"),
            'IMAGE_ROOTURL_MAIN': JSON.stringify('https://photonew.ux168.cn'),
            'IMAGE_ROOTURL_SELDOM': JSON.stringify("https://m2.uxcell.com"),
            //productcentr_nodejs地址
            'PRODUCTCENTER_URL': JSON.stringify('http://master.api.gateway.ux168.cn/productcenter/'),
            'PRODUCTCENTER_KEY': JSON.stringify('8sMLcEBcxEFUDH8gJBrHo2noRkiCT7maXhJrp1zw'),
            "UX168_FTP_URL": JSON.stringify("https://ali-productimages.ux168.com"),
            "CSAMS_WEB_URL": JSON.stringify("https://csams.ux168.cn"),
            'DATA_CENTER_URL': JSON.stringify("https://master-nodejs-datacenter.ux168.cn/api"),
            'DATA_CENTER_KEY': JSON.stringify(""),
            'PPMS_URL': JSON.stringify('https://master-nodejs-ppms.ux168.cn/api'),
            'PPMS_IMAGE_URL': JSON.stringify("https://ali-productimages.ux168.com/ppms/vote_image/"),
            'PPMS_WEB_URL': JSON.stringify("https://ppms-ssl.ux168.cn/"),
            "CETS_APP_URL_REST_QUERY": JSON.stringify("https://master-nodejs-cets.ux168.cn/api"),
            'CETS_APP_URL_REST_QUERY_KEY': JSON.stringify(""),
            "CETS_LOGISTICS_URL": JSON.stringify("https://master-nodejs-cets-logisticscost.ux168.cn/api"),
            "CETS_LOGISTICS_KEY": JSON.stringify(""),

            "CHANNEL_NODE_URL": JSON.stringify("http://master.nodejs.channel.ux168.cn:60000/api"),
            "CHANNEL_NODE_KEY": JSON.stringify(""),
            'FTP_IMAGE_URL': JSON.stringify("https://ali-productimages.ux168.com/poms/predict_market_image/"),
            'FTP_MRO_IMAGE_URL': JSON.stringify("https://ali-productimages.ux168.com/poms/mro_product_image/"),
            'STOCK_MANAGE_CRESULT': JSON.stringify("https://master-nodejs-stockmanagecalcresult.ux168.cn/api"),
            // 'USER_CENTER_AUTHORIZATION': JSON.stringify('http://check.poms.ux168.cn/uc_client/getBpmUser.php')
            'USER_CENTER_AUTHORIZATION': JSON.stringify('https://poms-ssl.ux168.cn/uc_client/getBpmUser.php'),
            "ANALYSIS_URL": JSON.stringify("https://query-esb.ux168.cn/mq_transfer/Esb_New_User_Action_Message_Transfer.php"),


            // 核心规格和关注点导出
            "PPMS_EXPORT_URL": JSON.stringify("https://gateway.ux168.cn/platform-ppms-application/"),
            // "PPMS_EXPORT_URL": JSON.stringify("https://gateway-test.ux168.cn/platform-ppms-application/"),

            //CSAMS node
            "CSAMS_NODEJS_APP_URL": JSON.stringify("https://master-nodejs-csams.ux168.cn/api"),
            "CSAMS_NODEJS_APP_KEY": JSON.stringify(""),

            //保存csa浏览量接口
            'UCENTER_URL': JSON.stringify('https://master-nodejs-ucenter.ux168.cn/api'),
            'UCENTER_KEY': JSON.stringify(''),

            'REVIEW_RECOMMEND_COMPLAINT_TYPE_URL': JSON.stringify('http://ai.ux168.cn:5000'),

            'TRANSLATION_URL': JSON.stringify('https://master-nodejs-translation.ux168.cn/api'),

            'POMS_PL_URL': JSON.stringify("https://master-nodejs-poms-pl.ux168.cn/api"),
            'POMS_PL_KEY': JSON.stringify(""),

            //翻译结算账单图片
            'FTP_PAYMENT_IMAGE_URL': JSON.stringify("https://ali-productimages.ux168.com/poms/translation_payment_image/"),

            'POMS_LOGS_URL': JSON.stringify("https://master-nodejs-poms-log.ux168.cn/api"),
            'POMS_LOGS_KEY': JSON.stringify(""),

            "POMS_BATCH_URL": JSON.stringify("https://master-nodejs-poms-batch.ux168.cn/api"),
            "POMS_BATCH_KEY": JSON.stringify(""),


            "PPMS_KEY": JSON.stringify(""),
            'FTP_PPMS_URL': JSON.stringify("https://ali-productimages.ux168.com/ppms"),
            'INVENTORY_WEB_URL': JSON.stringify('http://alivpc.web.inventory.ux168.cn'),

            'INCENTIVE_PL_NEW_URL': JSON.stringify("https://app-incentive-pl.ux168.cn"),

            //客服系统微信公众号相关配置
            'PHP_RESTFUL_URL_WECHAT_SERVER': JSON.stringify("https://external-api-gateway.ux168.cn/wechat-server"),
            'UX168_NEW_MOBILE_URL': JSON.stringify('https://m.ux168.com'),
            'LMS_NODE_URL': JSON.stringify('https://master-nodejs-lms.ux168.cn/api'),
            'HG_ERP_URL': JSON.stringify('https://master-nodejs-hg-erp.ux168.cn/api'),
            'HG_ERP_KEY': JSON.stringify(''),

            // aliexpressToken管理
            "ALIEXPRESS_API_PHP_RESTFUL": JSON.stringify("http://test.aliexpress.api.ux168.com/api/"),

            //亚马逊 A+ 图
            "FTP_PHOTO_A_URL": JSON.stringify("https://ali-productimages.ux168.com/poms/photo_a/"),

            //pa开发目录上传图片
            "FTP_PA_IMAGE_URL": JSON.stringify("https://ali-productimages.ux168.com/poms/pa_product_images/"),
            "FTP_HG_IMAGE_URL": JSON.stringify("https://ali-productimages.ux168.com/poms/hg_product_images/"),
            "CSAMS_URL": JSON.stringify("https://master-nodejs-csams.ux168.cn"),
            "POMS_FTP_IMAGE_URL": JSON.stringify("https://ali-productimages.ux168.com/poms/"),
            "POMS_FTP_URL": JSON.stringify("https://poms-ftp.ux168.cn"),
            'POMS_NEST_JS_URL': JSON.stringify("https://master-nodejs-poms-list-nest.ux168.cn/api"),
            'CETS_WEB_URL': JSON.stringify("https://alivpc-web-cets.ux168.cn"),
            'CNT_CSA_IMAGES_URL': JSON.stringify("https://ali-csamsimages.ux168.com"),

            "POMS_BIG_DATA_KEY": JSON.stringify(""),
            "POMS_BIG_DATA_URL": JSON.stringify("https://master-nodejs-poms-big-data-nest.ux168.cn/api"),

            "CETS_LOGS_NODE_APP_URL": JSON.stringify("https://master-nodejs-cets-logs.ux168.cn/api"),
            "CETS_LOGS_NODE_APP_KEY": JSON.stringify(""),

            'FEATURE_URL': JSON.stringify('https://feature-api.ux168.com'),

            'OPC_APP_URL_REST_QUERY': JSON.stringify('https://master-nodejs-opc.ux168.cn/api'),
            'OPC_APP_URL_REST_KEY': JSON.stringify(""),

            //运营中心
            'OPC_URL': JSON.stringify('http://opc.ux168.cn'),

            "DINGDING_OR_WX_CONFIG_QMS": JSON.stringify({
                "sendToDefault": "n",
                "sendDingDing": "y",
                "sendWX": "n",
                "wxUser": "卓明镜|18617368086",
                "ddUserList": "liyepeng"
            }),

            'POMS_ORIGINAL_LISTING_NEST_JS_URL': JSON.stringify("https://poms-original-listing-nest.ux168.cn/api"),
            'POMS_MF_WEB': JSON.stringify("https://poms-mf-ssl.ux168.cn"),

            // amazon模板
            'AMAZON_SP_APLUSCONTENT_URL': JSON.stringify("https://hk-alivpc-slim-poms-ext-sellingpartner.ux168.cn/api"),
            'AMAZON_SP_APLUSCONTENT_KEY': JSON.stringify(""),

            //cets_node图片浏览地址
            "CETS_IMAGE_UPLOAD_URL": JSON.stringify("https://ali-storage.ux168.cn/cets/upload/"),

            "MRO_PP_ML_URL": JSON.stringify("https://mro-pp-ml.ux168.cn"),

            "UCNETER_NETST_URL": JSON.stringify("https://master-nestjs-ucenter.ux168.cn/api"),

            'KEEPA_HK_MASTER_URL': JSON.stringify("http://hk.slim.poms.ux168.cn:8000/api"),
            'KEEPA_HK_QUERY_URL': JSON.stringify("http://hk.slim.poms.ux168.cn:8000/api"),
            'KEEPA_HK_KEY': JSON.stringify(""),

            'POMS_EXTERNAL_NEST_JS_URL': JSON.stringify("https://poms-external-nest.ux168.cn/api"),

            //amazon新账号 模板
            'NEW_AMAZON_SP_APLUSCONTENT_URL': JSON.stringify("https://hk-alivpc-slim-poms-sellingpartner.ux168.cn/api"),
            'NEW_AMAZON_SP_APLUSCONTENT_KEY': JSON.stringify(""),

            "PLATFORM_POMS_GOODS_URL": JSON.stringify("https://gateway.ux168.cn/platform-pomsgoods-service"),
            // "PLATFORM_POMS_GOODS_URL": JSON.stringify("http://127.0.0.1:9021"),
            "PLATFORM_POMS_GOODS_KEY": JSON.stringify(""),

            //新的vats
            'PLATFORM_CONFIG_MGMT_APPLICATION_URL': JSON.stringify("https://gateway.ux168.cn"),
            'PLATFORM_CONFIG_MGMT_APPLICATION_TOKEN': JSON.stringify("dd63d1ec-3b31-4a15-a05a-1ea5daa5aeb0"),

            //新架构网关token
            'PLATFORM_TOKEN': JSON.stringify("bearer dd63d1ec-3b31-4a15-a05a-1ea5daa5aeb0"),

            // platform-sms-support-application-service
            'PLATFORM_SMS_SUPPORT_APPLICATION_URL': JSON.stringify("https://gateway.ux168.cn/platform-sms-support-application"),
            'PLATFORM_SMS_CORE_APPLICATION_URL': JSON.stringify('https://gateway.ux168.cn/platform-sms-core-application'),

            //log日志服务
            'LOG_SERVER_URL': JSON.stringify('https://gateway.ux168.cn/ux168-log-service'),

            // csa的PLM项目
            "CSA_PRODUCT_SERVICE_URL": JSON.stringify("https://gateway.ux168.cn/csa-product-service"),
            "CSA_PRODUCT_SERVICE_TOKEN": JSON.stringify("324e6f02-5e0d-4c18-9fa5-3598363fe4a8"),
            "CSA_PRODUCT_SERVICE_KEY": JSON.stringify(""),

            // 大数据接口
            'BIG_DATE_URL': JSON.stringify("https://api-bigdata-5101ssl.ux168.cn/api"),

            // stock_manage_rpam
            'STOCK_MANAGE_RPAM': JSON.stringify("https://master-nodejs-stockmanagerpam.ux168.cn/api"),

            'PLATFORM_CONFIG_SERVICE_URL': JSON.stringify("https://gateway.ux168.cn/platform-config-service"),

            'GET_WAY_URL': JSON.stringify("https://gateway.ux168.cn"),

            //OBD服务
            'OBD_SMS_APPLICATION_URL': JSON.stringify("https://gateway.ux168.cn/obd-sms-application"),

            // sms前端项目
            'SMS_UX168_WEB': JSON.stringify("https://sms.ux168.cn"),

            "MRO_BIZ_WEB": JSON.stringify("https://mro-biz-web-test.ux168.cn"),

            //  qms前端项目
            'QMS_UX168_WEB': JSON.stringify("https://qms.ux168.cn/"),

            'USER_CENTER': JSON.stringify("http://alivpc.login.ux168.cn/index_entry.php"),


            // PA-SMS的后台服务
            'PA_SMS_BIZ_APPLICATION_URL': JSON.stringify("https://gateway.ux168.cn/pa-biz-application/sms"),

            // PA的pa-biz-application调用
            'PA_SCMS_BIZ_APPLICATION_URL': JSON.stringify("https://gateway.ux168.cn/pa-biz-application/scms"),

            'PA_IMS_BIZ_APPLICATION_URL': JSON.stringify("https://gateway.ux168.cn/pa-biz-application/ims"),
            //platform_wms_application
            'PLATFORM_WMS_APPLICATION_URL': JSON.stringify("https://gateway.ux168.cn/platform-wms-application"),

            //ux168 admin
            'UX168_ADMIN_URL': JSON.stringify("https://admin.ux168.com"),

            //ux168 admin hyperf
            'UX168_ADMIN_HYPERF_URL': JSON.stringify("https://ux168-admin-api.ux168.cn"),

            // mro的mro-biz-application调用poms
            'MRO_POMS_APPLICATION_URL': JSON.stringify('https://gateway.ux168.cn/mro-biz-application'),

            'POMS_JS_WEB_URL': JSON.stringify('https://poms-mf-ssl.ux168.cn/'),

            'UC_TOKEN_COOKIE_NAME': JSON.stringify('uc_token_production')
        }
    }
};
