#include "testlib.h"

int main(int argc, char* argv[])
{
    registerTestlibCmd(argc, argv);

    std::string pans = ouf.readLine();  // 读取选手输出的一行字符串
    std::string jans = ans.readLine();  // 读取标准答案的一行字符串

    if (pans == jans)
        quitf(_ok, "OK, accepted.");
    else
        quitf(_wa, "Your answer is incorrect.");
}
