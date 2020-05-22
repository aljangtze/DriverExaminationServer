# -*- coding: utf-8 -*-
import configparser
import os

#cfg = configparser.ConfigParser()
#cfg.read('config.ini')
cfg_file = configparser.ConfigParser()
configFile ="info.ini";
#configFile = r"D:\Projects\Webchat\DringTestServer\info.ini";
cfg_file.read(configFile,'utf-8');

for root, dirs, files in os.walk(r'..\..\..\Questions'):
    for fileName in files:
        cfg = configparser.ConfigParser()
        print(fileName)
        filePath = r"..\..\..\Questions" + "\\"  + fileName;
        #filePath = r"D:\Projects\Webchat\DringTestServer\Questions\10001.txt";

       #try:

        cfg.read(filePath,'utf-8')
            
        #except Exception as ex:
        #     print(filePath);
        #continue;
            


        question_id = cfg.get('QuesitonInfo', 'Id')
        #章节分组1~12
        chapter_id = cfg.get('QuesitonInfo', 'MoudleId')
        if int(chapter_id) == 0:
           continue
           
        if not cfg_file.has_option('chapter_info', chapter_id):
            cfg_file.set('chapter_info', chapter_id, '')
                               
        info = cfg_file.get('chapter_info', chapter_id)
        info = info + question_id + ','
        cfg_file.set( 'chapter_info', chapter_id, info)
        
        
        #1客车 2货车 3 小车科目一 4科目四 5摩托车 6摩托车科目四
        car_type = cfg.get('QuesitonInfo', 'Classification')
        if not cfg_file.has_option('car_type_info', car_type):
            cfg_file.set('car_type_info', car_type, '')
                               
        info = cfg_file.get('car_type_info', car_type)
        info = info + question_id + ','
        cfg_file.set( 'car_type_info', car_type, info)

        #题目类型1判断题2选择题3多选题
        question_type = cfg.get('QuesitonInfo', 'Type')
        if not cfg_file.has_option('question_type_info', question_type):
            cfg_file.set('question_type_info', question_type, '')
                               
        info = cfg_file.get('question_type_info', question_type)
        info = info + question_id + ','
        cfg_file.set( 'question_type_info', question_type, info)
        
	#题目对应的章节信息
        if not cfg_file.has_option('question_chapter_info', question_id):
            cfg_file.set('question_chapter_info', question_id, '')
                               
        info = cfg_file.get('question_chapter_info', question_id)
        info = info + chapter_id
        cfg_file.set( 'question_chapter_info', question_id, info)
        
        #分汽车、客车、小车、摩托车对题库进行分类
        if car_type=='3':
            if not cfg_file.has_option('car_0_info', question_type):
                cfg_file.set('car_0_info', question_type, '')
            info = cfg_file.get('car_0_info', question_type)
            info = info + question_id + ','
            cfg_file.set( 'car_0_info', question_type, info)

            option = str(int(question_type)*100 + int(chapter_id))
            if not cfg_file.has_option('car_0_info', option):
                cfg_file.set('car_0_info', option, '')
            info = cfg_file.get('car_0_info', option)
            info = info + question_id + ','
            cfg_file.set( 'car_0_info', option, info)
        #客车
        if car_type=='1':
            if not cfg_file.has_option('bus_0_info', question_type):
                cfg_file.set('bus_0_info', question_type, '')
            info = cfg_file.get('bus_0_info', question_type)
            info = info + question_id + ','
            cfg_file.set( 'bus_0_info', question_type, info)
            
            option = str(int(question_type)*100 + int(chapter_id))
            if not cfg_file.has_option('bus_0_info', option):
                cfg_file.set('bus_0_info', option, '')
            info = cfg_file.get('bus_0_info', option)
            info = info + question_id + ','
            cfg_file.set( 'bus_0_info', option, info)
        #货车
        if car_type=='2':
            if not cfg_file.has_option('trunck_0_info', question_type):
                cfg_file.set('trunck_0_info', question_type, '')
            info = cfg_file.get('trunck_0_info', question_type)
            info = info + question_id + ','
            cfg_file.set( 'trunck_0_info', question_type, info)
            option = str(int(question_type)*100 + int(chapter_id))
            
            if not cfg_file.has_option('trunck_0_info', option):
                cfg_file.set('trunck_0_info', option, '')
            info = cfg_file.get('trunck_0_info', option)
            info = info + question_id + ','
            
            cfg_file.set( 'trunck_0_info', option, info)
		#小车科目四
        if car_type=='4':
            if not cfg_file.has_option('car_1_info', question_type):
                cfg_file.set('car_1_info', question_type, '')
            info = cfg_file.get('car_1_info', question_type)
            info = info + question_id + ','
            cfg_file.set( 'car_1_info', question_type, info)
            
            option = str(int(question_type)*100 + int(chapter_id))
            if not cfg_file.has_option('car_1_info', option):
                cfg_file.set('car_1_info', option, '')
            info = cfg_file.get('car_1_info', option)
            info = info + question_id + ','
            cfg_file.set( 'car_1_info', option, info)
		#摩托车
        if car_type=='5':
            if not cfg_file.has_option('motor_0_info', question_type):
                cfg_file.set('motor_0_info', question_type, '')
            info = cfg_file.get('motor_0_info', question_type)
            info = info + question_id + ','
            cfg_file.set( 'motor_0_info', question_type, info)
            option = str(int(question_type)*100 + int(chapter_id))
            
            if not cfg_file.has_option('motor_0_info', option):
                cfg_file.set('motor_0_info', option, '')
            info = cfg_file.get('motor_0_info', option)
            info = info + question_id + ','
            
            cfg_file.set( 'motor_0_info', option, info)

        if car_type=='6':
            if not cfg_file.has_option('motor_1_info', question_type):
                cfg_file.set('motor_1_info', question_type, '')
            info = cfg_file.get('motor_1_info', question_type)
            info = info + question_id + ','
            cfg_file.set( 'motor_1_info', question_type, info)
            option = str(int(question_type)*100 + int(chapter_id))
            
            if not cfg_file.has_option('motor_1_info', option):
                cfg_file.set('motor_1_info', option, '')
            info = cfg_file.get('motor_1_info', option)
            info = info + question_id + ','
            
            cfg_file.set( 'motor_1_info', option, info)        

        #技巧章节
        if cfg.has_option('QuesitonInfo', 'SkillId'):
            skill_id = cfg.get('QuesitonInfo', 'SkillId')
            if not cfg_file.has_option('skill_info', skill_id):
                cfg_file.set('skill_info', skill_id, '')
                               
            info = cfg_file.get('skill_info', skill_id)
            info = info + question_id + ','
            cfg_file.set( 'skill_info', skill_id, info)

        #自分类章节
        if cfg.has_option('QuesitonInfo', 'BankId'):
            bank_id = cfg.get('QuesitonInfo', 'BankId')
            if not cfg_file.has_option('bank_info', bank_id):
                cfg_file.set('bank_info', bank_id, '')
                               
            info = cfg_file.get('bank_info', bank_id)
            info = info + question_id + ','
            cfg_file.set( 'bank_info', bank_id, info)

    
cfg_file.write(open(configFile, 'w'))
print('success')

#更新分类的题目数量
for option in cfg_file.options('car_type_info'):
    #print(option)
    info = cfg_file.get('car_type_info', option)
    #print(info)
    number = str(len(info.split(','))-1)
    print(number)
    cfg_file.set('car_type_info_count', option, number)
    
for option in cfg_file.options('chapter_info'):
    #print(option)
    info = cfg_file.get('chapter_info', option)
    #print(info)
    number = str(len(info.split(','))-1)
    print(number)
    cfg_file.set('chapter_info_count', option, number)

for option in cfg_file.options('question_type_info'):
    #print(option)
    info = cfg_file.get('question_type_info', option)
    #print(info)
    number = str(len(info.split(','))-1)
    print(number)
    cfg_file.set('question_type_info_count', option, number)

for option in cfg_file.options('skill_info'):
    #print(option)
    info = cfg_file.get('skill_info', option)
    #print(info)
    number = str(len(info.split(','))-1)
    print(number)
    cfg_file.set('skill_info_count', option, number)
for option in cfg_file.options('bank_info'):
    #print(option)
    info = cfg_file.get('bank_info', option)
    #print(info)
    number = str(len(info.split(','))-1)
    print(number)
    cfg_file.set('bank_info_count', option, number)
    
cfg_file.write(open(configFile, 'w'))
