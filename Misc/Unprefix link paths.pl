# `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
# Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/QW`@

# `! +REPLACEME=\[.*\]
chdir q[C:\My\Database];
@files = glob "*\\*";

foreach my $file (@files) {
  if ($file =~ /\\\d+$/) {
    open(my $in, $file) or die $!;
    my $content = <$in>;
    # .\ is superfluous, could be just an empty part.
    $content =~ s/^C:\\My\\Database\\/.\\/;   # `! +REPLACEME=C:[\w\\]*?
    open(my $out, '>', $file) or die $!;
    print $out $content;
  }
}
