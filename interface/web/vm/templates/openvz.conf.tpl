
# Template version
VERSION="2"

# Parameters
ONBOOT="{tmpl_var name='onboot'}"
BOOTORDER="{tmpl_var name='bootorder'}"

# VSwap requires RAM and SWAP, all other memory parameters are optional.
<tmpl_if name='physpages'>
# RAM
PHYSPAGES="{tmpl_var name='physpages'}"
</tmpl_if>
<tmpl_if name='swappages'>
# SWAP
SWAPPAGES="{tmpl_var name='swappages'}"
</tmpl_if>

<tmpl_if name='kmemsize'>
KMEMSIZE="{tmpl_var name='kmemsize'}"
</tmpl_if>
<tmpl_if name='lockedpages'>
LOCKEDPAGES="{tmpl_var name='lockedpages'}"
</tmpl_if>
<tmpl_if name='privvmpages'>
PRIVVMPAGES="{tmpl_var name='privvmpages'}"
</tmpl_if>
<tmpl_if name='shmpages'>
SHMPAGES="{tmpl_var name='shmpages'}"
</tmpl_if>
<tmpl_if name='vmguarpages'>
VMGUARPAGES="{tmpl_var name='vmguarpages'}"
</tmpl_if>
<tmpl_if name='oomguarpages'>
OOMGUARPAGES="{tmpl_var name='oomguarpages'}"
</tmpl_if>
# alternative meminfo: "pages:256000"
MEMINFO="privvmpages:1"

<tmpl_if name='vmguarpages'>
NUMPROC="{tmpl_var name='numproc'}"
</tmpl_if>
<tmpl_if name='numtcpsock'>
NUMTCPSOCK="{tmpl_var name='numtcpsock'}"
</tmpl_if>
<tmpl_if name='numflock'>
NUMFLOCK="{tmpl_var name='numflock'}"
</tmpl_if>
<tmpl_if name='numpty'>
NUMPTY="{tmpl_var name='numpty'}"
</tmpl_if>
<tmpl_if name='numsiginfo'>
NUMSIGINFO="{tmpl_var name='numsiginfo'}"
</tmpl_if>
<tmpl_if name='tcpsndbuf'>
TCPSNDBUF="{tmpl_var name='tcpsndbuf'}"
</tmpl_if>
<tmpl_if name='tcprcvbuf'>
TCPRCVBUF="{tmpl_var name='tcprcvbuf'}"
</tmpl_if>
<tmpl_if name='othersockbuf'>
OTHERSOCKBUF="{tmpl_var name='othersockbuf'}"
</tmpl_if>
<tmpl_if name='dgramrcvbuf'>
DGRAMRCVBUF="{tmpl_var name='dgramrcvbuf'}"
</tmpl_if>
<tmpl_if name='numothersock'>
NUMOTHERSOCK="{tmpl_var name='numothersock'}"
</tmpl_if>
<tmpl_if name='dcachesize'>
DCACHESIZE="{tmpl_var name='dcachesize'}"
</tmpl_if>
<tmpl_if name='numfile'>
NUMFILE="{tmpl_var name='numfile'}"
</tmpl_if>
<tmpl_if name='avnumproc'>
AVNUMPROC="{tmpl_var name='avnumproc'}"
</tmpl_if>
<tmpl_if name='numiptent'>
NUMIPTENT="{tmpl_var name='numiptent'}"
</tmpl_if>

DISKSPACE="{tmpl_var name='diskspace'}"
DISKINODES="{tmpl_var name='diskinodes'}"
QUOTAUGIDLIMIT="10000"
QUOTATIME="0"
<tmpl_if name='io_priority'>
IOPRIO="{tmpl_var name='io_priority'}"
</tmpl_if>

<tmpl_if name='cpu_num'>
CPUS="{tmpl_var name='cpu_num'}"
</tmpl_if>
<tmpl_if name='cpu_units'>
CPUUNITS="{tmpl_var name='cpu_units'}"
</tmpl_if>
<tmpl_if name='cpu_limit'>
CPULIMIT="{tmpl_var name='cpu_limit'}"
</tmpl_if>

VE_ROOT="/vz/root/$VEID"
VE_PRIVATE="/vz/private/$VEID"
OSTEMPLATE="{tmpl_var name='ostemplate'}"
ORIGIN_SAMPLE="vps.basic"
HOSTNAME="{tmpl_var name='hostname'}"
IP_ADDRESS="{tmpl_var name='ip_address'}"
NAMESERVER="{tmpl_var name='nameserver'}"

<tmpl_if name='capability'>
CAPABILITY="{tmpl_var name='capability'}"
</tmpl_if>
<tmpl_if name='features'>
FEATURES="{tmpl_var name='features'}"
</tmpl_if>
<tmpl_if name='iptables'>
IPTABLES="{tmpl_var name='iptables'}"
</tmpl_if>
<tmpl_if name='custom'>
{tmpl_var name='custom'}
</tmpl_if>
