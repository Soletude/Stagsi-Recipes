; `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
; Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/NF`@

#Persistent
OnClipboardChange:
StringLeft, Start, Clipboard, 7
if (Start = "Stagsi ")
{
  StrReplace(Clipboard, """FileLink"": null,", , CopiedCount)
  StrReplace(Clipboard, """FileLink"": ", , TotalCount)
  LinkedCount := TotalCount - CopiedCount
  ToolTip %LinkedCount% out of %TotalCount% objects are linked
  Sleep 5000
  ToolTip
}
return
