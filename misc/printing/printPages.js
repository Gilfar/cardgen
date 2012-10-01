
/*
This is a script for Adobe Photoshop CS. To run it...

1. Go to File -> Scripts -> Browse.
2. Choose this script, click OK.
3. Choose a directory that contains pages of PNG files (page1.png, page2.png, etc).
4. Photoshop will automatically send all the pages to the printer with high quality.

Replace the appropriate three lines below with the following for A4 paper:

desc3.putUnitDouble( id9, id10, 595.230525 );
desc3.putUnitDouble( id11, id12, 841.793323 );
desc3.putUnitDouble( id13, id14, 314.500000 );

To recreate this whole script yourself, find \Photoshop CS\Scripting Guide\Utilities\ScriptListener.8li and copy it into \Photoshop CS\Plug-Ins\Extensions\. Restart Photoshop. Now any action you take is recorded in a js and vbscript file at C:\. When you are ready, delete the js file there (if it exists), then do this:

Open an image to print.
Ctrl + A
Ctrl + C
Ctrl + F4
Ctrl + N
Enter the correct values to create a new 314.5 DPI (if not possible: 315 DPI) document that is the right size for your image.
Hit Ok.
Ctrl + V
Ctrl + P

Now go to the js file and it will contain all the script to do those actions automatically. Replace the part of my script with yours. Make sure you update your script with line below marked "*****".

*/

var folder = Folder.selectDialog("Choose image output directory");
if (folder) {
	var i = 1;
	while (new File(folder + "/page" + i + ".png").exists) {



		// =======================================================
		var id5 = charIDToTypeID( "Mk  " );
		 var desc2 = new ActionDescriptor();
		 var id6 = charIDToTypeID( "Nw  " );
			  var desc3 = new ActionDescriptor();
			  var id7 = charIDToTypeID( "Md  " );
			  var id8 = charIDToTypeID( "RGBM" );
			  desc3.putClass( id7, id8 );
			  var id9 = charIDToTypeID( "Wdth" );
			  var id10 = charIDToTypeID( "#Rlt" );
			  desc3.putUnitDouble( id9, id10, 595.230525 );
			  var id11 = charIDToTypeID( "Hght" );
			  var id12 = charIDToTypeID( "#Rlt" );
			  desc3.putUnitDouble( id11, id12, 744.038156 );
			  var id13 = charIDToTypeID( "Rslt" );
			  var id14 = charIDToTypeID( "#Rsl" );
			  desc3.putUnitDouble( id13, id14, 314.500000 );
			  var id15 = stringIDToTypeID( "pixelScaleFactor" );
			  desc3.putDouble( id15, 1.000000 );
			  var id16 = charIDToTypeID( "Fl  " );
			  var id17 = charIDToTypeID( "Fl  " );
			  var id18 = charIDToTypeID( "Trns" );
			  desc3.putEnumerated( id16, id17, id18 );
			  var id19 = charIDToTypeID( "Dpth" );
			  desc3.putInteger( id19, 16 );
			  var id20 = stringIDToTypeID( "profile" );
			  desc3.putString( id20, "sRGB IEC61966-2.1" );
		 var id21 = charIDToTypeID( "Dcmn" );
		 desc2.putObject( id6, id21, desc3 );
		executeAction( id5, desc2, DialogModes.NO );

		// =======================================================
		var id92 = charIDToTypeID( "Opn " );
			 var desc14 = new ActionDescriptor();
			 var id93 = charIDToTypeID( "null" );
			 // ***** Make sure you do this in your generated script!
			 desc14.putPath( id93, new File( folder + "/page" + i + ".png" ) );
		executeAction( id92, desc14, DialogModes.NO );

		// =======================================================
		var id94 = charIDToTypeID( "setd" );
			 var desc15 = new ActionDescriptor();
			 var id95 = charIDToTypeID( "null" );
				  var ref7 = new ActionReference();
				  var id96 = charIDToTypeID( "Chnl" );
				  var id97 = charIDToTypeID( "fsel" );
				  ref7.putProperty( id96, id97 );
			 desc15.putReference( id95, ref7 );
			 var id98 = charIDToTypeID( "T   " );
			 var id99 = charIDToTypeID( "Ordn" );
			 var id100 = charIDToTypeID( "Al  " );
			 desc15.putEnumerated( id98, id99, id100 );
		executeAction( id94, desc15, DialogModes.NO );

		// =======================================================
		var id101 = charIDToTypeID( "copy" );
		executeAction( id101, undefined, DialogModes.NO );

		// =======================================================
		var id102 = charIDToTypeID( "Cls " );
		executeAction( id102, undefined, DialogModes.NO );

		// =======================================================
		var id103 = charIDToTypeID( "past" );
			 var desc16 = new ActionDescriptor();
			 var id104 = charIDToTypeID( "AntA" );
			 var id105 = charIDToTypeID( "Annt" );
			 var id106 = charIDToTypeID( "Anno" );
			 desc16.putEnumerated( id104, id105, id106 );
		executeAction( id103, desc16, DialogModes.NO );

		// =======================================================
		var id107 = charIDToTypeID( "Prnt" );
			 var desc17 = new ActionDescriptor();
		executeAction( id107, desc17, DialogModes.NO );

		// =======================================================
		var id108 = charIDToTypeID( "Cls " );
			 var desc18 = new ActionDescriptor();
			 var id109 = charIDToTypeID( "Svng" );
			 var id110 = charIDToTypeID( "YsN " );
			 var id111 = charIDToTypeID( "N   " );
			 desc18.putEnumerated( id109, id110, id111 );
		executeAction( id108, desc18, DialogModes.NO );



		i++;
	}
}
