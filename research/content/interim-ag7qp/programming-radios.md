---
title: "Programming Radios"
source_url: "https://ag7qp.com/programming-radios"
fetched: "2026-09-25"
raw_html: "research/external/ag7qp/programming-radios.html"
nav_parent: "General Information"
site: "ag7qp.com (Hostinger Website Builder; sitemap lastmod 2026-09-23)"
---
> Converted from the ag7qp.com interim page. Site-wide header nav and footer omitted. Personal phone numbers / e-mail addresses replaced with "[redacted: contact detail]".

### Options for Programming Your Radios

**Programming a radio means putting information into a memory location, usually called a channel, that enables communication with another person/location, via simplex or a repeater. Programming a radio requires:**\
Setting the receive frequency\
Setting the transmit frequency (may be the same as the receive frequency)\
Setting any receive tone (may not be required)\
Setting any transmit tone (also may not be required)\
Storing a stored frequency (Putting the information into a storage location, i.e., channel.)

**We start with some definitions:**\
**SQL** = controls how much background noise comes through

**in-band signaling options** (Depends on radio manufacturer)\
**TSQL** = Tone that tells the repeater to listen\
**DCS** = an acronym for Digital Coded Squelch. It is a set of digital codes that act as filters to eliminate interference from unwanted transmissions originated by other individuals or groups who are operating on the same channel or frequency.\
**DTCS** = Digital Tone Coded Squelch, uses digital tones.\
**CTCSS** = Continuous Tone-Coded Squelch System is used to reduce unwanted radio signals or interference.

**Modes**\
**Channel Mode** = Pre-defined frequencies and settings\
**VFO Mode** = Free form

**Simplex Programming** (Person-to-person, single frequency)\
Checklist for simplex settings:\
- Frequency\
- Make sure duplex is NOT enabled\
- Tone mode (None, Tone, TSQL, DCS/DTCS)\
 > Tone encode and decode settings are separate on land mobile radios and not a single “tone mode” menu option\
- Set tone value or DCS code if needed\
 > 88.5Hz, 123.0Hz, etc. for Tone and TSQL\
 > 023, 036, etc. for DCS and DTCS\
**Simplex Programming Steps: (**Make sure duplex is NOT enabled)\
Part 97 (Ham) radios Part 90 (Commercial) radios\
1. Frequency (Be in VFO mode) | 1. Receive Frequency\
2. Tone Mode (None/Tone/ TSQL/DCS) | 2. Transmit Frequency\
3. Tone Value | 3. Receive Tone\
 4. Transmit Tone\
**Repeater Programming**\
Checklist for repeater settings:\
– Frequency (this is the repeater's transmit frequency/your receive frequency)\
– Duplex\
 > May need to be enabled\
– Offset direction and value (*from the repeater’s transmit frequency*)\
 > If standard, this is often pre-programmed\
 > May be in two separate menus (*Direction and value*)\
– Tone mode (None, Tone, TSQL, DCS/DTCS)(*There are two options*)\
– Tone value or DCS code\
 > 88.5Hz, 123.0Hz, etc. for Tone and TSQL\
 > 023, 036, etc. for DCS and DTCS

**Repeater Programming Steps:** (May have to enable Duplex Operation)\
Part 97 (Ham) radios Part 90 (Commercial) radios\
1. Receive Frequency | 1. Receive Frequency\
2. Offset Direction | 2. Transmit Frequency\
3. Offset Amount | 3. Receive Tone\
4. Tone Mode | 4. Transmit Tone\
5. Tone Value

**Options for Programming Your Radios -** you can program a radio with a computer or manually by pressing the buttons. Some of the newest radios allow radio-to-radio programming and others offer Bluetooth programming. Check your owner's manual for details about your radio.\
Computer Options:\
- [RT Systems](https://www.rtsystemsinc.com/): RT Systems software and cables absolutely work. Programming is basically filling out a spreadsheet, then uploading to your radio. Software is $25, Cable is $30 and you can get both for $49 - required for each radio model. RT Systems has a 38 page “Getting Started with the Programmer” guide.\
- [CHIRP](https://chirpmyradio.com/projects/chirp/wiki/Home): a free, open-source tool for programming your radio. It supports a large number of manufacturers and models, as well as provides a way to interface with multiple data sources and formats. Documentation is a wiki. It is a single program that does many radio brands/models. Needs a programming cable, generally offered with radio. Programming is basically filling out a spreadsheet, then uploading to your radio. Baofeng software of choice\
- Manufacturer's Software: Check on the manufacturer's website for details and information.

What Port Are You Using: All the computer programming software requires that you tell it what port you are using.\
1. Right click on the Windows button\
2. Select Device Manager\
3. Select Ports (COM and LPT)\
4. Identify the COM Port being used for the programming cable\
5. Use that COM Port in CHIRP

Manual Programming: (Check your owner's manual for the specific steps for your radio. The steps given here are for Baofeng HTs. Names may change. Mobile radios are much the same. )\
- Frequency is done in VFO mode (Switch to VFO mode and enter the frequency using the number buttons.)\
- Basic Instructions for everything else: (Read your owner's manual to identify the Menu and Exit buttons)\
 > Press Menu\
 > Use the Up and Down keys to navigate to Function\
 > Press Menu\
 > Set the parameter\
 > Press Menu to save & return to main Menu\
 > Press Exit

**Baofeng HT Menu Items** (required to program a memory channel)\
Power = 2\
RX CTCSS = 11\
TX CTCSS = 13\
Offset (Shift) Direction = 25\
Offset (Shift) Value = 26\
Save Channel Number = 27\
Delete Channel Number = 28

[Baofeng UV-5R Step-by-Step Manual Programming Instructions](https://www.baofengradio.com/blogs/news/baofeng-uv-5r-manual-programming-instructions?srsltid=AfmBOorNljKEkHtmgdWnGQGsVtZMry7bLOuv-vtbDZS9BMnzL_8IbcBN)

**Radio Programming Field Expedient Cheat Sheets** (If you have one and are willing to share, please send it to me [redacted: contact detail].\
 [Baofeng UV-5R](https://assets.zyrosite.com/AVLpnMOEDxUWzNvE/manually-programming-your-baofeng-uv-5ra-m7Vb02JXE3i3x4jQ.pdf) (Full Page) [Baofeng UV-5R (Half Page)](https://assets.zyrosite.com/AVLpnMOEDxUWzNvE/manually-programming-your-baofeng-uv-5ra---half-page-size-Yg2yV7eJl9u1lpEK.pdf)\
 [Baofeng UV-82](https://assets.zyrosite.com/AVLpnMOEDxUWzNvE/baofeng-hts-AVL1jM0lJDfDoMyx.docx)\
 [TIDRADIO TD-H3](https://assets.zyrosite.com/AVLpnMOEDxUWzNvE/manually-programming-yourtidradio-td-h3---half-page-size-m6Lb0X7GRgskbWKo.pdf)\
 ICOM IC-7300\
 [Snohomish County Version](https://assets.zyrosite.com/AVLpnMOEDxUWzNvE/icom-ic-7300-cheat-sheet-m7V5GRN1Z0fDQ5zJ.docx)\
 FEH Version\
 [QYT KT-8900](https://assets.zyrosite.com/AVLpnMOEDxUWzNvE/how-to-program-qyt-kt-8900-YNqBE6LKVBsa7L1D.docx)\
 [TYT TH-9800](https://assets.zyrosite.com/AVLpnMOEDxUWzNvE/tyt-th-9800-AwvDwl16roikLE4m.docx)\
 [Yaesu FTM-6000R](https://assets.zyrosite.com/AVLpnMOEDxUWzNvE/yaesu-ftm-6000-one-pager-A85EGWbg1xUWGO8D.docx)
